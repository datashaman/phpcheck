<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Types;

use Icecave\Repr\Generator;

class Just extends Maybe
{
    public function stringRepresentation(Generator $generator, $currentDepth = 0)
    {
        return $generator->generate('<Just ' . $this->value . '>');
    }
}
