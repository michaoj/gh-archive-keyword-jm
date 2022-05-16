<?php

namespace App\Message;

/**
 * Class EventFile
 *
 * @category gh-archive-keyword-jm
 * @package  App\Message
 * @author   Joachim Martin <joachim.martin@emilfrey.fr>
 */
class EventFile
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
