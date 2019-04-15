<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * (c) Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Generator;
use Webmozart\Assert\Assert;

class Gen
{
    protected const DEFAULT_SIZE = 30;

    public const MIN_UNICODE = 0;

    public const MAX_UNICODE = 0x10FFFF;

    public const EXCLUDE_UNICODE = [
        [0x000000, 0x00001F],
        [0x00D800, 0x00DFFF],
        [0x00E000, 0x00F8FF],
        [0x0F0000, 0x0FFFFD],
        [0x100000, 0x10FFFD],
    ];

    /**
     * @var FakerGenerator
     */
    protected $faker;

    /**
     * @var Runner
     */
    protected $runner;

    public function __construct(Runner $runner, int $seed = null)
    {
        $this->faker  = Factory::create();
        $this->runner = $runner;

        $seed = $seed ?: (int) \microtime() * 1000000;
        \mt_srand($seed);
    }

    public function booleans(int $chanceOfGettingTrue = 50): Generator
    {
        Assert::natural($chanceOfGettingTrue);
        Assert::lessThanEq($chanceOfGettingTrue, 100);

        while (true) {
            yield \mt_rand(1, 100) <= $chanceOfGettingTrue;
        }
    }

    public function floats(
        float $min = \PHP_FLOAT_MIN,
        float $max = \PHP_FLOAT_MAX,
        Generator $decimals = null
    ): Generator {
        Assert::lessThanEq($min, $max);

        if (null === $decimals) {
            $decimals = $this->integers(0, 9);
        }

        $iteration = 0;

        /*
         * @var int
         */
        foreach ($decimals as $decimal) {
            $f = \max(
                $min,
                \min(
                    \round($min + \mt_rand() / \mt_getrandmax() * $iteration / ($this->runner->getMaxIterations() - 1) * ($max - $min), $decimal),
                    $max
                )
            );

            yield $f;
            $iteration++;
        }
    }

    public function integers(
        int $min = \PHP_INT_MIN,
        int $max = \PHP_INT_MAX
    ): Generator {
        Assert::lessThanEq($min, $max);

        $currentMax = $min;

        $iteration = 0;

        while (true) {
            $currentMax = \max(
                $min,
                \min(
                    (int) ($iteration / ($this->runner->getMaxIterations() - 1) * ($max - $min) + $min),
                    $max
                )
            );

            yield \mt_rand($min, $currentMax);
            $iteration++;
        }
    }

    public function intervals(
        array $include = [[\PHP_INT_MIN, \PHP_INT_MAX]],
        array $exclude = []
    ): Generator {
        Assert::isList($include);
        Assert::minCount($include, 1);

        $intervals = [];

        $max = 0;

        foreach ($include as $interval) {
            Assert::isList($interval);
            Assert::count($interval, 2);
            Assert::allNatural($interval);

            [$start, $end] = $interval;

            Assert::lessThanEq($start, $end);

            $size = $end - $start;

            $intervals[] = [$max, $size - 1, $start];

            $max += $size;
        }

        $integers = $this->integers(0, $max - 1);

        foreach ($integers as $integer) {
            foreach ($intervals as $interval) {
                $start = $interval[0];

                $end = $interval[1];

                $value = $interval[2];

                if ($integer >= $start && $integer <= $end) {
                    $value = $value + $integer - $start;

                    foreach ($exclude as $excludeInterval) {
                        $start = $excludeInterval[0];
                        $end   = $excludeInterval[1];

                        if ($value >= $start && $value <= $end) {
                            break 2;
                        }
                    }

                    yield $value;

                    break;
                }
            }
        }
    }

    /**
     * @param null|int|string $minChar a character, or its codepoint or ordinal value
     * @param null|int|string $maxChar a character, or its codepoint or ordinal value
     */
    public function characters(
        $minChar = null,
        $maxChar = null
    ): Generator {
        if (null === $minChar) {
            $minCodepoint = self::MIN_UNICODE;
        } elseif (\is_string($minChar)) {
            $minCodepoint = $minChar === '' ? self::MIN_UNICODE : \mb_ord($minChar);
        } else {
            Assert::integer($minChar);
            $minCodepoint = $minChar;
        }

        if (null === $maxChar) {
            $maxCodepoint = self::MAX_UNICODE;
        } elseif (\is_string($maxChar)) {
            $maxCodepoint = \mb_ord($maxChar);
        } else {
            Assert::integer($minChar);
            $maxCodepoint = $maxChar;
        }

        Assert::lessThanEq($minCodepoint, $maxCodepoint);
        Assert::greaterThanEq($minCodepoint, self::MIN_UNICODE);
        Assert::lessThanEq($maxCodepoint, self::MAX_UNICODE);

        $codepoints = $this->intervals(
            [
                [$minCodepoint, $maxCodepoint],
            ],
            self::EXCLUDE_UNICODE
        );

        foreach ($codepoints as $codepoint) {
            yield \mb_chr($codepoint);
        }
    }

    public function strings(
        Generator $sizes = null,
        Generator $characters = null
    ): Generator {
        if (null === $sizes) {
            $sizes = $this->integers(0, self::DEFAULT_SIZE);
        }

        if (null === $characters) {
            $characters = $this->characters();
        }

        foreach ($sizes as $size) {
            $result = '';

            if ($size === 0) {
                yield $result;
            }

            while ($characters->valid()) {
                $result .= (string) $characters->current();

                if (\mb_strlen($result) >= $size) {
                    yield $result;

                    break;
                }

                $characters->next();
            }
        }
    }

    public function ascii(
        Generator $sizes = null
    ): Generator {
        if (null === $sizes) {
            $sizes = $this->integers(0, self::DEFAULT_SIZE);
        }

        $strings = $this->strings(
            $sizes,
            $this->characters(0, 0x7F)
        );

        foreach ($strings as $string) {
            yield $string;
        }
    }

    public function choose(
        array $arr
    ): Generator {
        Assert::notEmpty($arr);
        Assert::isList($arr);

        $positions = $this->integers(0, \count($arr) - 1);

        foreach ($positions as $position) {
            yield $arr[$position];
        }
    }

    /**
     * @param Generator $sizes
     */
    public function listOf(
        Generator $values,
        Generator $sizes = null
    ): Generator {
        if (null === $sizes) {
            $sizes = $this->integers(0, self::DEFAULT_SIZE);
        }

        foreach ($sizes as $size) {
            $result = [];

            while ($values->valid()) {
                $result[] = $values->current();

                if (\count($result) >= $size) {
                    yield $result;

                    break;
                }

                $values->next();
            }
        }
    }

    public function faker(
        ...$args
    ) {
        Assert::greaterThanEq(\count($args), 1);

        $attr = \array_shift($args);

        while (true) {
            if (\count($args)) {
                yield \call_user_func([$this->faker, $attr], ...$args);
            } else {
                yield $this->faker->{$attr};
            }
        }
    }
}
