<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Events;

use ReflectionMethod;

class EndEvent extends Event
{
    /**
     * @var ReflectionMethod
     */
    public $method;

    /**
     * @var array
     */
    public $tags;

    /**
     * @var string
     */
    public $status;

    public function __construct(
        ReflectionMethod $method,
        array $tags,
        string $status
    ) {
        parent::__construct();
        $this->method = $method;
        $this->tags = $tags;
        $this->status = $status;
    }
}
