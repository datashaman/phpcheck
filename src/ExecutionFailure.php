<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck;

use Exception;
use ReflectionMethod;
use Throwable;

class ExecutionFailure extends Exception
{
    public $args;
    public $cause;

    public function __construct(
        array $args,
        Throwable $cause
    ) {
        parent::__construct(
            sprintf(
                "args=%s resulted in failure '%s'",
                json_encode($args),
                $cause->getMessage()
            )
        );

        $this->args = $args;
        $this->cause = $cause;
    }
}
