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

use Savvot\Random\XorShiftRand;

class Random extends XorShiftRand
{
    public function random($min = 0, $max = null)
    {
        do {
            $value = parent::random($min, $max);
        } while (!\is_int($value));

        return $value;
    }
}
