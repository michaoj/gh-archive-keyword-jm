<?php

namespace App\MessageHandler;

use App\Message\Event;
use App\Message\EventFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class EventFileHandler
 *
 * @category gh-archive-keyword-jm
 * @package  App\MessageHandler
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
#[AsMessageHandler]
class EventFileHandler
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface     $ghImportLogger
    ) {
    }

    public function __invoke(EventFile $eventFile): void
    {
        $currentFile = $eventFile->getContent();
        $process = new Process(['gunzip', $currentFile]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // We will use a stream to get all events in order to avoir memory issues
        $events = fopen(str_replace('.gz','', $currentFile), 'rb');

        while(true)
        {
            // Get new gh line
            $event = fgets($events);
            // If null, it means we reach the end of the file
            if (!$event)
            {
                break;
            }

            try {
                $ev = json_decode($event, true, 512, JSON_THROW_ON_ERROR);
                if (in_array($ev['type'], ['PushEvent', 'PullRequestEvent', 'CommitCommentEvent'])) {
                    $this->bus->dispatch(new Event($event));
                }
            } catch (\JsonException $e) {
                $this->ghImportLogger->error($e->getMessage());
            }
        }
        fclose($events);
    }
}
