<?php

namespace App\Repository;

use App\Entity\Actor;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ActorReadEventRepository
 *
 * @category gh-archive-keyword-jm
 * @package  App\Repository
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
class DbalReadActorRepository implements DbalReadActorRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {

        $this->entityManager = $entityManager;
    }

    public function findOneById(array $actor): Actor
    {
        if (isset($actor['id'])) {
            $qb = $this->entityManager->createQueryBuilder()
                ->select('a')
                ->from(Actor::class, 'a')
                ->where('a.id = :id')
                ->setParameter('id', $actor['id']);

            if ($existingActor = $qb->getQuery()->getOneOrNullResult()) {
                return $existingActor;
            }
        }
        return Actor::fromArray($actor);
    }
}
