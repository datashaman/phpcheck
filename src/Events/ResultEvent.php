<?php

declare(strict_types=1);

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
     * @var array|null
     */
    public $args;

    /**
     * @var Throwable|null
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
        $this->args = $args;
        $this->cause = $cause;
    }
}
