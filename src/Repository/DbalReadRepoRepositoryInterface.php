<?php

namespace App\Repository;

use App\Entity\Repo;

/**
 * Interface DbalReadRepoRepositoryInterface
 *
 * @category gh-archive-keyword-jm
 * @package  App\Repository
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
interface DbalReadRepoRepositoryInterface
{
    public function findOneById(array $repo):Repo ;
}
