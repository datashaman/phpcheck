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

use DateTime;
use DateTimeZone;
use Ds\Map;
use Exception;
use Faker\Generator as FakerGenerator;
use Webmozart\Assert\Assert;

class MkGen
{
    use Traits\LogTrait;
    use Traits\SeedTrait;

    public const DEFAULT_SIZE = 30;

    public const UNICODE_EXCLUDE = [
        [0x000000, 0x00001F],
        [0x00D800, 0x00DFFF],
        [0x00E000, 0x00F8FF],
        [0x0F0000, 0x0FFFFD],
        [0x100000, 0x10FFFD],
    ];

    public const UNICODE_MIN = 0;

    public const UNICODE_MAX = 0x10FFFF;

    protected const TYPE_GENERATORS = [
        'array'  => 'arrays',
        'bool'   => 'booleans',
        'float'  => 'floats',
        'int'    => 'choose',
        'mixed'  => 'mixed',
        'string' => 'strings',
    ];

    public function arguments(callable $callable): Gen
    {
        static $cache;

        if (!isset($cache)) {
            $cache = new Map();
        }

        if (!$cache->hasKey($callable)) {
            $function = reflection()->reflect($callable);

            $generators = [];

            foreach ($function->getParameters() as $param) {
                $tags = reflection()->getParamTags($param);

                if (\array_key_exists('gen', $tags)) {
                    $generator = app('tagParser')->parse($tags['gen']);
                } else {
                    $paramType = $param->hasType() ? $param->getType() : null;
                    $type      = $paramType ? $paramType->getName() : 'mixed';

                    if (!\array_key_exists($type, self::TYPE_GENERATORS)) {
                        throw new Exception("No generator found for $type");
                    }

                    $generator = self::TYPE_GENERATORS[$type];
                    $generator = $this->$generator();
                }

                $generators[] = $generator;
            }

            $generator = new Gen(
                function ($seed, $size) use ($generators) {
                    $arguments = [];

                    foreach ($generators as $generator) {
                        $arguments[] = $generator
                            ->generate($seed, $size);
                    }

                    return $arguments;
                }
            );

            $cache->put($callable, $generator);
        }

        return $cache->get($callable);
    }

    public function arrays(): Gen
    {
        $this->logExecution('mkGen', 'arrays');

        return new Gen(
            function ($seed, $size) {
                return $this
                    ->listOf($this->mixed())
                    ->generate($seed, $size);
            }
        );
    }

    public function ascii(): Gen
    {
        $this->logExecution('mkGen', 'ascii');

        return new Gen(
            function ($seed, $size) {
                return $this
                    ->characters(0, 0x7F)
                    ->generate($seed, $size);
            }
        );
    }

    public function booleans(int $chanceOfGettingTrue = 50): Gen
    {
        $this->logExecution('mkGen', 'booleans', 50);

        return new Gen(
            function () use ($chanceOfGettingTrue) {
                return \mt_rand(1, 100) <= $chanceOfGettingTrue;
            }
        );
    }

    public function characters(
        $minChar = null,
        $maxChar = null
    ): Gen {
        $this->logExecution('mkGen', 'characters', [$minChar, $maxChar]);

        if (null === $minChar) {
            $minCodepoint = self::UNICODE_MIN;
        } elseif (\is_string($minChar)) {
            $minCodepoint = $minChar === '' ? self::UNICODE_MIN : \mb_ord($minChar);
        } else {
            Assert::integer($minChar);
            $minCodepoint = $minChar;
        }

        if (null === $maxChar) {
            $maxCodepoint = self::UNICODE_MAX;
        } elseif (\is_string($maxChar)) {
            $maxCodepoint = \mb_ord($maxChar);
        } else {
            Assert::integer($minChar);
            $maxCodepoint = $maxChar;
        }

        Assert::lessThanEq($minCodepoint, $maxCodepoint);
        Assert::greaterThanEq($minCodepoint, self::UNICODE_MIN);
        Assert::lessThanEq($maxCodepoint, self::UNICODE_MAX);

        $codePoints = $this->intervals(
            [
                [$minCodepoint, $maxCodepoint],
            ],
            self::UNICODE_EXCLUDE
        );

        return new Gen(
            function ($seed, $size) use ($codePoints) {
                $codepoint = $codePoints->generate($seed);

                if (is_null($codepoint)) {
                    return null;
                }

                return \mb_chr($codepoint);
            }
        );
    }

    public function choose($min = PHP_INT_MIN, $max = PHP_INT_MAX): Gen
    {
        $this->logExecution('mkGen', 'choose', [$min, $max]);

        if (\is_array($min)) {
            [$min, $max] = $min;
        }

        $strings = false;

        if (\is_string($min)) {
            $strings = true;
            $min     = \mb_ord($min);
        }

        if (\is_string($max)) {
            $strings = true;
            $max     = \mb_ord($max);
        }

        Assert::lessThanEq($min, $max);

        return new Gen(
            function ($seed, $size) use ($min, $max, $strings) {
                if (\is_float($min) || \is_float($max)) {
                    $value = $this->floats($min, $max)->generate();

                    return $value;
                }

                $value = \mt_rand($min, $max);

                return $strings ? \mb_chr($value) : $value;
            }
        );
    }

    public function chooseAny(string $type): Gen
    {
        $this->logExecution('mkGen', 'chooseAny', $type);

        return new Gen(
            function () use ($type) {
                switch ($type) {
                    case 'float':
                        return $this
                            ->floats(\PHP_FLOAT_MIN, \PHP_FLOAT_MAX)
                            ->generate();
                    case 'int':
                        return $this
                            ->choose(\PHP_INT_MIN, \PHP_INT_MAX)
                            ->generate();
                    case 'string':
                        return $this->characters()
                            ->generate();
                    default:
                        throw new Exception('Unhandled type');
                }
            }
        );
    }

    public function datetimes(
        $min = null,
        $max = null,
        Gen $timezones = null
    ): Gen {
        $this->logExecution('mkGen', 'datetimes', [$min, $max]);

        if (null === $min) {
            $min = new DateTime('0001-01-01 00:00:00.000000');
        }

        if (null === $max) {
            $max = new DateTime('9999-01-01 23:59:59.999999');
        }

        if (\is_string($min)) {
            $min = new DateTime($min);
        }

        if (\is_string($max)) {
            $max = new DateTime($max);
        }

        if (\is_array($min)) {
            [$min, $max] = $min;
        }

        Assert::lessThanEq($min, $max);

        return new Gen(
            function () use ($min, $max, $timezones) {
                $timestamp = $this
                    ->choose(
                        $min->getTimestamp(),
                        $max->getTimestamp()
                    )
                    ->generate();

                $datetime = new DateTime("@$timestamp");

                if ($timezones) {
                    $timezone = new DateTimeZone($timezones->generate());
                    $datetime->setTimezone($timezone);
                }

                return $datetime;
            }
        );
    }

    public function dates($min = null, $max = null): Gen
    {
        $this->logExecution('mkGen', 'dates', [$min, $max]);

        if (null === $min) {
            $min = new DateTime('0001-01-01');
        }

        if (null === $max) {
            $max = new DateTime('9999-01-01');
        }

        if (\is_array($min)) {
            [$min, $max] = $min;
        }

        Assert::lessThanEq($min, $max);

        return new Gen(
            function () use ($min, $max) {
                $datetime = $this
                    ->datetimes($min, $max)
                    ->generate();

                $datetime->setTime(0, 0, 0);

                return $datetime;
            }
        );
    }

    public function elements(array $array): Gen
    {
        $this->logExecution('mkGen', 'elements', $array);

        Assert::notEmpty($array);

        return new Gen(
            function () use ($array) {
                $position = $this
                    ->choose(0, \count($array) - 1)->generate();

                return $array[$position];
            }
        );
    }

    public function faker(...$args): Gen
    {
        $this->logExecution('mkGen', 'faker', $args);

        Assert::greaterThanEq(\count($args), 1);

        $attr = \array_shift($args);
        $faker = app('faker');

        return new Gen(
            function () use ($attr, $args, $faker) {
                if (\count($args)) {
                    return \call_user_func([$faker, $attr], ...$args);
                }

                return $faker->{$attr};
            }
        );
    }

    public function floats($min, $max): Gen
    {
        $this->logExecution('mkGen', 'floats', [$min, $max]);

        Assert::lessThanEq($min, $max);

        return new Gen(
            function () use ($min, $max) {
                $value = \max(
                    $min,
                    \min(
                        $min + \mt_rand() / \mt_getrandmax() * ($max - $min),
                        $max
                    )
                );

                return $value;
            }
        );
    }

    public function frequency(array $frequencies): Gen
    {
        $this->logExecution('mkGen', 'frequencies', $frequencies);

        Assert::notEmpty($frequencies);

        return new Gen(
            function () use ($frequencies) {
                $map = new Map();

                foreach ($frequencies as $frequency) {
                    [$weighted, $gen] = $frequency;
                    $map->put($gen, $weighted);
                }

                return $this
                    ->pick($map)
                    ->generate();
            }
        );
    }

    public function growingElements(array $array): Gen
    {
        return new Gen(
            function ($seed, $size) use ($array) {
                $k = \count($array);
                $mx = 100;
                $log_ = function ($value) {
                    return \round(\log($value));
                };
                $size = (int) (($log_($size) + 1) * \floor($k / $log_($mx)));

                return $this
                    ->elements(\array_slice($array, 0, \max(1, $size)))
                    ->generate($seed);
            }
        );
    }

    public function intervals(
        array $include = [[\PHP_INT_MIN, \PHP_INT_MAX]],
        array $exclude = []
    ): Gen {
        $this->logExecution('mkGen', 'intervals', \compact('include', 'exclude'));

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

        return new Gen(
            function ($seed) use ($exclude, $intervals, $max) {
                $min = 0;

                while (true) {
                    $integer = $this
                        ->choose(0, $max)->generate($seed);

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

                            return $value;
                        }
                    }
                }
            }
        );
    }

    public function listOf(Gen $gen): Gen
    {
        $this->logExecution('mkGen', 'listOf', $gen);

        return new Gen(
            function ($seed, $size) use ($gen) {
                $newSize = $this
                    ->choose(0, $size)
                    ->generate($seed);

                return $this
                    ->vectorOf($newSize, $gen)
                    ->generate($seed);
            }
        );
    }

    public function listOf1(Gen $gen): Gen
    {
        $this->logExecution('mkGen', 'listOf1', $gen);

        return new Gen(
            function ($seed, $size) use ($gen) {
                $newSize = $this
                    ->choose(1, \max(1, $size))
                    ->generate($seed);

                return $this
                    ->vectorOf($newSize, $gen)
                    ->generate($seed);
            }
        );
    }

    public function mixed(): Gen
    {
        $this->logExecution('mkGen', 'mixed');

        return new Gen(
            function ($seed, $size) {
                return $this
                    ->oneof(
                        $this->strings(),
                        $this->choose(),
                        $this->booleans(),
                        $this->dates(),
                        $this->datetimes(),
                        $this->listOf($this->strings()),
                        $this->listOf($this->choose())
                    )
                    ->generate($seed, $size);
            }
        );
    }

    public function oneof(...$gens): Gen
    {
        $this->logExecution('mkGen', 'oneof', $gens);

        return new Gen(
            function ($seed) use ($gens) {
                $choice = $this
                    ->choose(0, \count($gens) - 1)
                    ->generate($seed);

                return $gens[$choice]->generate();
            }
        );
    }

    public function pick(Map $weighted): Gen
    {
        $this->logExecution('mkGen', 'pick', $weighted);

        $rand = \mt_rand(1, (int) $weighted->values()->sum());

        foreach ($weighted as $key => $value) {
            $rand -= $value;

            if ($rand <= 0) {
                return $key;
            }
        }
    }

    public function resize(int $size, Gen $gen): Gen
    {
        $this->logExecution('mkGen', 'resize', [$size, $gen]);

        return new Gen(
            function ($seed) use ($size, $gen) {
                return $gen->generate($seed, $size);
            }
        );
    }

    public function sample(Gen $gen): Gen
    {
        $this->logExecution('mkGen', 'sample', $gen);

        $sizes = \range(0, 20, 2);

        return new Gen(
            function ($seed) use ($gen, $sizes) {
                return \array_map(
                    function ($size) use ($gen) {
                        return $gen
                            ->generate($seed, $size);
                    },
                    $sizes
                );
            }
        );
    }

    public function scale(callable $callable, Gen $gen): Gen
    {
        $this->logExecution('mkGen', 'scale', ['callable', $gen]);

        return new Gen(
            function ($seed) use ($callable, $gen) {
                $newSize = \call_user_func($callable, $gen->size);

                return $gen
                    ->generate($seed, $newSize);
            }
        );
    }

    public function strings(Gen $characters = null): Gen
    {
        $this->logExecution('mkGen', 'strings');

        if (is_null($characters)) {
            $characters = $this->characters();
        }

        return new Gen(
            function ($seed, $size) use ($characters) {
                $newSize = $this
                    ->choose(0, $size)->generate();
                $result = '';

                while (\mb_strlen($result) < $newSize) {
                    $result .= $characters->generate($seed);
                }

                $result = mb_substr($result, 0, $newSize);

                return $result;
            }
        );
    }

    public function suchThat(Gen $gen, callable $callable): Gen
    {
        $this->logExecution('mkGen', 'suchThat', [$gen, 'callable']);

        return new Gen(
            function ($seed, $size) use ($gen, $callable) {
                $mx = $this
                    ->suchThatMaybe($gen, $callable)
                    ->generate($seed);

                if ($mx->isJust()) {
                    return $mx->value();
                }

                if ($mx->isNothing()) {
                    return $this
                        ->suchThat($gen, $callable)
                        ->generate($seed, $size + 1);
                }

                throw new Exception('This should not be possible');
            }
        );
    }

    public function suchThatMap(Gen $gen, callable $callable): Gen
    {
        $this->logExecution('mkGen', 'suchThatMap', [$gen, 'callable']);

        return new Gen(
            function ($seed) use ($gen, $callable) {
                $value = $this
                    ->suchThat($gen, $callable)
                    ->generate($seed);

                $result = \call_user_func($callable, $value);

                if (!$result instanceof Types\Maybe) {
                    throw new Exception('Callable must return Maybe type');
                }

                return $result->value();
            }
        );
    }

    public function suchThatMaybe(Gen $gen, callable $callable): Gen
    {
        $this->logExecution('mkGen', 'suchThatMaybe', [$gen, 'callable']);

        $try = function ($current, $maximum, $seed) use ($callable, $gen, &$try) {
            if ($current > $maximum) {
                return new Types\Nothing();
            }

            $item = $gen->generate($seed, $current);

            if (\call_user_func($callable, $item)) {
                return new Types\Just($item);
            }

            return \call_user_func($try, $current + 1, $maximum, $seed);
        };

        return new Gen(
            function ($seed) use ($gen, $try) {
                return $try($gen->size, $gen->size * 2, $seed);
            }
        );
    }

    public function timezones(): Gen
    {
        $this->logExecution('mkGen', 'timezones');

        $zones     = \timezone_identifiers_list();
        $positions = $this
            ->choose(0, \count($zones) - 1);

        return new Gen(
            function ($seed) use ($positions, $zones) {
                $position = $positions->generate($seed);

                return $zones[$position];
            }
        );
    }

    public function variant(int $seed, Gen $gen): Gen
    {
        $this->logExecution('mkGen', 'variant', [$seed, $gen]);

        return new Gen(
            function () use ($seed, $gen) {
                return $gen->generate($seed);
            }
        );
    }

    public function vectorOf(int $size, Gen $gen): Gen
    {
        $this->logExecution('mkGen', 'vectorOf', [$size, $gen]);

        return new Gen(
            function ($seed) use ($size, $gen) {
                $result = [];
                $count = 0;

                while (\count($result) < $size) {
                    $result[] = $gen->generate($seed);
                }

                return $result;
            }
        );
    }
}
