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

class ExecutionFailure extends Exception
{
    protected $args;

    public function __construct(array $args)
    {
        parent::__construct(
            \sprintf(
                'args=%s resulted in failure',
                \json_encode($args)
            )
        );

        $this->args  = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }
}
