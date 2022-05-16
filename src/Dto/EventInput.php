<?php

namespace App\Dto;

use App\Entity\Actor;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\Repo;
use App\Repository\DbalReadActorRepositoryInterface;
use App\Repository\DbalReadRepoRepositoryInterface;
use Monolog\DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class EventInput
{
    /**
     * @Assert\Length(min=20)
     */
    public ?string $comment;

    private ?array $decoded;
    private Actor  $actor;
    private Repo   $repo;


    public function __construct(
        ?string $comment,
        Actor $actor,
        Repo $repo
    ) {
        $this->comment = $comment;
        $this->actor = $actor;
        $this->repo = $repo;

        try {
            $this->decoded = json_decode($comment, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
        }

    }

    public function getEvent(): Event
    {
        try {
            return new Event(
                $this->decoded['id'],
                $this->getType(),
                $this->actor,
                $this->repo,
                $this->decoded['payload'],
                new DateTimeImmutable($this->decoded['created_at']),
                ''
            );
        } catch (\Exception $e) {
        }
    }


    private function getType()
    {
        switch ($this->decoded['type']) {
            case 'PushEvent':
                return EventType::COMMIT;
            case 'PullRequestEvent':
                return EventType::PULL_REQUEST;
            case 'CommitCommentEvent':
                return EventType::COMMENT;
        }
    }
}
