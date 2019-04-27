<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Exceptions;

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
