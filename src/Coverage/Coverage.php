<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Coverage;

/**
 * Abstract base class for coverage classes.
 */
abstract class Coverage
{
    public function __destruct()
    {
        global $coverage;

        $coverage->stop();
    }
}
