<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Events;

use ReflectionMethod;

class EndEvent extends Event
{
    /**
     * @var ReflectionMethod
     */
    public $method;

    /**
     * @var string
     */
    public $status;

    public function __construct(ReflectionMethod $method, string $status)
    {
        parent::__construct();
        $this->method = $method;
        $this->status = $status;
    }
}
