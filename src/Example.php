<?php

namespace Datashaman\PHPCheck;

use Exception;

class Example extends Exception
{
    public $args;

    public function __construct(array $args)
    {
        parent::__construct("Found Example");
        $this->args = $args;
    }
}
