<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Datashaman\PHPCheck;

use Exception;
use Throwable;

class ExecutionFailure extends Exception
{
    protected $args;

    protected $cause;

    public function __construct(
        array $args,
        Throwable $cause
    ) {
        parent::__construct(
            \sprintf(
                "args=%s resulted in failure '%s'",
                \json_encode($args),
                $cause->getMessage()
            )
        );

        $this->args  = $args;
        $this->cause = $cause;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function getCause()
    {
        return $this->cause;
    }
}
