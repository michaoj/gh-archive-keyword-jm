<?php

namespace App\Repository;

use App\Entity\Repo;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DbalReadRepoRepository
 *
 * @category gh-archive-keyword-jm
 * @package  App\Repository
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
class DbalReadRepoRepository implements DbalReadRepoRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findOneById(array $repo): Repo
    {
        if (isset($repo['id'])) {
            $qb = $this->entityManager->createQueryBuilder()
                ->select('r')
                ->from(Repo::class, 'r')
                ->where('r.id = :id')
                ->setParameter('id', $repo['id']);

            if ($existingRepo = $qb->getQuery()->getOneOrNullResult()) {
                return $existingRepo;
            }
        }
        return Repo::fromArray($repo);
    }
}
