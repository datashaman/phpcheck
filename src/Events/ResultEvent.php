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
use Throwable;

abstract class ResultEvent extends Event
{
    /**
     * @var ReflectionMethod
     */
    public $method;

    /**
     * @var null|array
     */
    public $args;

    /**
     * @var null|Throwable
     */
    public $cause;

    /**
     * @var string
     */
    public $status;

    public function __construct(
        ReflectionMethod $method,
        array $args = null,
        Throwable $cause = null
    ) {
        parent::__construct();
        $this->method = $method;
        $this->args   = $args;
        $this->cause  = $cause;
    }
}
