<?php

namespace Datashaman\PHPCheck;

use Exception;

class CheckError extends Exception
{
    public $code;
    public $file;
    public $line;

    public function __construct(
        string $message = '',
        int $code = 0,
        string $file = null,
        int $line = null
    ) {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}
