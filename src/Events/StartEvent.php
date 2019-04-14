<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Events;

use ReflectionMethod;

class StartEvent extends Event
{
    /**
     * @var ReflectionMethod
     */
    public $method;

    public function __construct(ReflectionMethod $method)
    {
        parent::__construct();
        $this->method = $method;
    }
}
