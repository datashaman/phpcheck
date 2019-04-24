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

class StartEvent extends Event
{
    /**
     * @var ReflectionMethod
     */
    public $method;

    /**
     * @var array
     */
    public $tags;

    public function __construct(
        ReflectionMethod $method,
        array $tags
    ) {
        parent::__construct();
        $this->method = $method;
        $this->tags = $tags;
    }
}
