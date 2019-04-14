<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Events;

abstract class Event extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var float
     */
    public $time;

    public function __construct()
    {
        $this->time = microtime(true);
    }
}
