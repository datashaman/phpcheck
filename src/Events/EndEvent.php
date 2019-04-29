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

use ReflectionFunctionAbstract;

class EndEvent extends Event
{
    /**
     * @var ReflectionFunctionAbstract
     */
    public $function;

    /**
     * @var array
     */
    public $tags;

    /**
     * @var string
     */
    public $status;

    public function __construct(
        ReflectionFunctionAbstract $function,
        array $tags,
        string $status
    ) {
        parent::__construct();
        $this->function = $function;
        $this->tags     = $tags;
        $this->status   = $status;
    }
}
