<?php

namespace App\Repository;

use App\Entity\Actor;

/**
 * Interface DbalReadActorRepositoryInterface
 *
 * @category gh-archive-keyword-jm
 * @package  App\Repository
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
interface DbalReadActorRepositoryInterface
{
    public function findOneById(array $actor): Actor;
}
