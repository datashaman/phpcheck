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

class Example extends Exception
{
    public $args;

    public function __construct(array $args)
    {
        parent::__construct('Found Example');
        $this->args = $args;
    }
}
