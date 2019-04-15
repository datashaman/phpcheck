<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * (c) Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Events;

abstract class Event extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var float
     */
    public $time;

    public function __construct()
    {
        $this->time = \microtime(true);
    }
}
