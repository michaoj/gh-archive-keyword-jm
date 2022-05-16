<?php

namespace App\Repository;

use App\Dto\EventInput;
use App\Entity\Event;

interface WriteEventRepository
{
    public function update(EventInput $authorInput, int $id): void;

    public function save(Event $event);
}
