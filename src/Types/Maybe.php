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
use Datashaman\PHPCheck\Types\Interfaces\MonadInterface;
use Icecave\Repr\RepresentableInterface;

abstract class Maybe extends Monad implements
    FunctorInterface,
    RepresentableInterface
{
    public static function unit($value = null): self
    {
        return null === $value
            ? new Nothing()
            : new Just($value);
    }

    public function map(callable $func): FunctorInterface
    {
        $this->value = self::unit(\call_user_func($func, $this->value));

        return $this;
    }

    public function isJust()
    {
        return $this instanceof Just;
    }

    public function isNothing()
    {
        return $this instanceof Nothing;
    }

    public function bind(
        callable $callable
    ): MonadInterface {
        $this->value = self::unit(\call_user_func($callable, $this->value));

        return $this;
    }
}
