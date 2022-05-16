<?php

namespace App\MessageHandler;

use App\Dto\EventInput;
use App\Message\Event;
use App\Repository\DbalReadActorRepositoryInterface;
use App\Repository\DbalReadRepoRepositoryInterface;
use App\Repository\DbalWriteEventRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Class EventHandler
 *
 * @category gh-archive-keyword-jm
 * @package  App\MessageHandler
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
#[AsMessageHandler]
class EventHandler
{
    public function __construct(
        private DbalReadActorRepositoryInterface $actorRepository,
        private DbalReadRepoRepositoryInterface  $repoRepository,
        private DbalWriteEventRepository         $eventRepository,
        private LoggerInterface                  $ghImportLogger
    ) {
    }

    public function __invoke(Event $event): void
    {
        try {
            $ev = json_decode($event->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->ghImportLogger->error($e->getMessage());
        }
        $actor = $this->actorRepository->findOneById($ev['actor'] ?? []);
        $repo = $this->repoRepository->findOneById($ev['repo'] ?? []);

        $eventInput = new EventInput($event->getContent(), $actor, $repo);
        $this->eventRepository->save($eventInput->getEvent());
    }
}
