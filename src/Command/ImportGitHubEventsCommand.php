<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\EventFile;
use DateInterval;
use DatePeriod;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * This command must import GitHub events.
 * You can add the parameters and code you want in this command to meet the need.
 */
#[AsCommand(
    name: 'app:import-github-events',
    description: 'Import GH events'
)]
class ImportGitHubEventsCommand extends Command
{
    private const IMPORT_PATH = '/var/import';

    public function __construct(
        private HttpClientInterface $ghClient,
        private LoggerInterface $ghImportLogger,
        private ValidatorInterface $validator,
        private KernelInterface $kernel,
        private MessageBusInterface $bus,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'start',
                mode: InputArgument::REQUIRED,
                description:'Mandatory interval start <comment>(format YYYY-MM-DD)</comment>'
            )
            ->addArgument(
                name: 'end',
                mode: InputArgument::OPTIONAL,
                description: 'Optional interval end <comment>(format YYYY-MM-DD)</comment>'
            )
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command performs parallel curl calls to the github events archive data 
It retrieves all data for each day in the interval
This command will generate messages in two queues. Hence, worker on those queues should be started

<error>IMPORTANT : Consumers should be running in prod environnement to avoid messagenger data collection by the profiler</error>

  <comment>start</comment> should be a valid date with <comment>YYYY-MM-DD</comment> format
  <comment>end</comment> is optional and should be a valid date with <comment>YYYY-MM-DD</comment> format, superior to the start one. Defaults to <comment>start</comment> if not set

  <info>php %command.full_name% 2022-04-01</info>            Will retrieve data for the full day 
  <info>php %command.full_name% 2022-04-01 2022-04-03</info> Will retrieve all data between 2022-04-01 and 2022-04-03 
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        // First ensure we have the required folder to store our imported files
        $fs = new Filesystem();

        $fullImportPath = $this->kernel->getProjectDir() . self::IMPORT_PATH;
        if (!$fs->exists($fullImportPath)) {
            $fs->mkdir($fullImportPath);
        }

        //  Check date interval
        $start = $input->getArgument('start');
        $end = $input->getArgument('end');

        if (!$end) {
            $end = $start;
        }

        $constraints = new Collection(
            [
                'fields' => [
                    'start' => new Date(),
                    'end'   => [
                        new Date(),
                        new GreaterThanOrEqual($start),
                    ]
                ]
            ]
        );

        $errors = $this->validator->validate(['start' => $start, 'end' => $end], $constraints);

        // TODO : better error handling => The display is not very good
        if (count($errors) > 0) {
            $message = count($errors) > 1 ? 'Invalids arguments : ' : 'Invalid argument : ';
            foreach ($errors as $error) {
                $message .= "\n" . $error->getPropertyPath() . ' : ' . $error->getMessage();
            }
            throw new InvalidArgumentException($message);
        }

        $this->ghImportLogger->info(
            message: 'Starting import for interval',
            context: [
                'start' => $start,
                'end' => $end
            ]
        );

        $datesToProcess = $this->getDatesToProcess($start, $end);

        $responses = [];
        $filesToDownload = $filesDownloaded = 0;

        // Loop over all dates and perform the calls
        foreach ($datesToProcess as $dateToProcess) {
            // Now we need to get all hours for the current day
            for($hour = 0; $hour < 24; ++$hour) {
                $currentFile = sprintf('/%s-%s.json', $dateToProcess, $hour);
                $uri = $currentFile . '.gz';
                $style->comment(sprintf('Downloading %s ...', $uri));
                // Check if file exists
                if (!$fs->exists($fullImportPath . $uri)) {
                    $this->ghImportLogger->info(
                        message: 'Downloading file',
                        context: ['currentFile' => $uri]
                    );
                    $filesToDownload++;
                    $responses[] = $this->ghClient->request(
                        method: 'GET',
                        url: $uri,
                        options: [
                            'user_data' => $fullImportPath . $uri
                        ]
                    );
                } else {
                    $this->ghImportLogger->warning(
                        message: 'The file has already been downloaded',
                        context: ['currentFile' => $currentFile]
                    );
                }
            }
        }

        try {// now loop over the responses and process the returns by chunk, thus we will avoid memory errors on large file
            foreach ($this->ghClient->stream($responses) as $response => $chunk) {
                if ($chunk->isFirst()) {
                    $this->ghImportLogger->info(
                        message: 'Saving file to disk',
                        context: ['currentFile' => $response->getInfo('user_data')]
                    );
                } elseif ($chunk->isLast()) {
                    $filesDownloaded++;
                    $style->success(sprintf('Completed download to %s', $response->getInfo('user_data')));
                    file_put_contents(
                        filename: $response->getInfo('user_data'),
                        data: $chunk->getContent(),
                        flags: FILE_APPEND
                    );
                    $this->ghImportLogger->info(
                        message: 'File saved',
                        context: ['currentFile' => $response->getInfo('user_data')]
                    );
                    $this->bus->dispatch(new EventFile($response->getInfo('user_data')));
                } else {
                    // Add new piece of the file
                    file_put_contents(
                        filename: $response->getInfo('user_data'),
                        data: $chunk->getContent(),
                        flags: FILE_APPEND
                    );
                }
            }
        } catch (TransportExceptionInterface $e) {
            $this->ghImportLogger->error($e->getMessage());
        }

        $returnMessage = sprintf('Downloaded %s/%s file(s)', $filesDownloaded, $filesToDownload);

        if ($filesToDownload === $filesDownloaded) {
            $style->success($returnMessage);
            $this->ghImportLogger->info(
                message: 'Import completed',
                context: [
                    'message' => $returnMessage,
                ]
            );
            return Command::SUCCESS;
        }

        if ($filesDownloaded > 0) {
            $style->warning($returnMessage);
        } else {
            $style->error($returnMessage);
        }
        $this->ghImportLogger->info(
            'Import finished with errors',
            [
                'message' => $returnMessage,
            ]
        );
        return Command::FAILURE;
    }

    private function getDatesToProcess($start, $end): array
    {
        try {
            $startDate = new DateTime($start);
            $endDate = new DateTime($end);

            // Compute date interval to process
            $datesToProcess = [$start];
            $interval = DateInterval::createFromDateString('1 day');
            $dateRange = new DatePeriod($startDate, $interval, $endDate);
            foreach ($dateRange as $aDate) {
                if (!in_array($aDate->format('Y-m-d'), $datesToProcess, true)) {
                    $datesToProcess[] = $aDate->format('Y-m-d');
                }
            }
            if ($end !== $start) {
                $datesToProcess[] = $end;
            }
            return $datesToProcess;
        } catch (\Exception $e) {
            $this->ghImportLogger->error($e->getMessage());
        }
        return [];
    }
}
