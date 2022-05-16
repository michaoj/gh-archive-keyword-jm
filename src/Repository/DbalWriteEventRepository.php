<?php

namespace App\Repository;

use App\Dto\EventInput;
use App\Dto\SearchInput;
use App\Entity\Event;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Author;

class DbalWriteEventRepository implements WriteEventRepository
{
    public function __construct(
        private Connection $connection,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function update(EventInput $authorInput, int $id): void
    {
        $sql = <<<SQL
        UPDATE event
        SET comment = :comment
        WHERE id = :id
SQL;

        $this->connection->executeQuery($sql, ['id' => $id, 'comment' => $authorInput->comment]);
    }

    public function save(Event $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
