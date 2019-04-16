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
use Webmozart\Assert\Assert;

class Gen
{
    public const MAX_TRIES = 20;

    public $gen;
    public $size;

    public function __construct(
        callable $gen,
        int $size = MkGen::DEFAULT_SIZE
    ) {
        $this->gen   = $gen;
        $this->size  = $size;
    }

    public function filter(callable $callable): self
    {
        return new self(
            function () use ($callable) {
                $tries = 0;

                while ($tries < self::MAX_TRIES) {
                    $value = $this->generate();

                    if (\call_user_func($callable, $value)) {
                        return $value;
                    }

                    $tries++;
                }

                throw new Exception("Could not find any valid examples in $tries tries");
            }
        );
    }

    public function flatmap(callable $callable): self
    {
        return new self(
            function () use ($callable) {
                $gen = \call_user_func($callable, $this->generate());

                Assert::isInstanceOf($gen, self::class);

                return $gen->generate();
            }
        );
    }

    public function generate(int $seed = null, int $size = null)
    {
        if (null !== $seed) {
            \mt_srand($seed);
        }

        if (null === $size) {
            $size = $this->size;
        }

        $result = \call_user_func(
            $this->gen,
            $seed,
            $size
        );

        if (null !== $seed) {
            \mt_srand(app('gen')->getSeed());
        }

        return $result;
    }

    public function map(callable $callable)
    {
        return new self(
            function () use ($callable) {
                return \call_user_func($callable, $this->generate());
            }
        );
    }
}
