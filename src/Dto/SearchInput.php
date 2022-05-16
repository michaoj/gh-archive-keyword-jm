<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SearchInput
{
    /**
     * @var \DateTimeImmutable
     * @Assert\NotNull())
     */
    public \DateTimeImmutable $date;

    /**
     * @var string
     * @Assert\NotNull())
     */
    public string $keyword;
}
