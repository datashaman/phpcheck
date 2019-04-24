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

use Datashaman\PHPCheck\Types\Interfaces\MonadInterface;

abstract class Monad implements
    MonadInterface
{
    protected $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function value()
    {
        return $this->value;
    }
}
