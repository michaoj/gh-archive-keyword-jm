<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SearchInput
{
    #[Assert\NotNull]
    public \DateTimeImmutable $date;

    #[Assert\NotNull]
    public string $keyword;
}
