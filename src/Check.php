<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck;

use Generator;
use ReflectionFunction;

class Check
{
    const ITERATIONS = 100;

    /**
     * @var Runner
     */
    protected $runner;

    /**
     * @var Gen
     */
    protected $gen;

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
        $this->gen = $runner->getGen();
    }
}
