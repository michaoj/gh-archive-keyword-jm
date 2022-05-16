<?php

namespace App\MessageHandler;

use App\Dto\EventInput;
use App\Message\Event;
use App\Repository\DbalReadActorRepositoryInterface;
use App\Repository\DbalReadRepoRepositoryInterface;
use App\Repository\DbalWriteEventRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Class EventHandler
 *
 * @category gh-archive-keyword-jm
 * @package  App\MessageHandler
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
class EventHandler implements MessageHandlerInterface
{
    private DbalReadActorRepositoryInterface $actorRepository;
    private DbalReadRepoRepositoryInterface  $repoRepository;
    private DbalWriteEventRepository         $eventRepository;
    private LoggerInterface                  $ghImportLogger;

    public function __construct(
        DbalReadActorRepositoryInterface $actorRepository,
        DbalReadRepoRepositoryInterface  $repoRepository,
        DbalWriteEventRepository         $eventRepository,
        LoggerInterface                  $ghImportLogger
    )
    {
        $this->actorRepository = $actorRepository;
        $this->repoRepository = $repoRepository;
        $this->eventRepository = $eventRepository;
        $this->ghImportLogger = $ghImportLogger;
    }

    public function __invoke(Event $event)
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
