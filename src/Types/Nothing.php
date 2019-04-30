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

use Datashaman\PHPCheck\Types\Interfaces\FunctorInterface;
use Exception;
use Icecave\Repr\Generator;

class Nothing extends Maybe
{
    public function stringRepresentation(Generator $generator, $currentDepth = 0)
    {
        return $generator->generate('<Nothing>');
    }

    public static function unit($value = null): Maybe
    {
        if (null !== $value) {
            throw new Exception('Nothing is nothing');
        }

        return parent::unit($value);
    }

    public function map(callable $func): FunctorInterface
    {
        $this->value = Maybe::unit(null);

        return $this;
    }
}
