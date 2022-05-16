<?php

namespace App\Message;

/**
 * Class Event
 *
 * @category gh-archive-keyword-jm
 * @package  App\Message
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
class Event
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
