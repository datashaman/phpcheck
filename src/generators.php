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

use Datashaman\PHPCheck\Types\Just;
use Datashaman\PHPCheck\Types\Maybe;
use Datashaman\PHPCheck\Types\Nothing;
use DateTime;
use DateTimeZone;
use Ds\Map;
use Exception;
use Generator;
use Webmozart\Assert\Assert;

const DEFAULT_SIZE = 30;

const UNICODE_EXCLUDE = [
    [0x000000, 0x00001F],
    [0x00D800, 0x00DFFF],
    [0x00E000, 0x00F8FF],
    [0x0F0000, 0x0FFFFD],
    [0x100000, 0x10FFFD],
];

const UNICODE_MIN = 0;

const UNICODE_MAX = 0x10FFFF;

const TYPE_GENERATORS = [
    'array'  => 'Datashaman\PHPCheck\arrays',
    'bool'   => 'Datashaman\PHPCheck\booleans',
    'float'  => 'Datashaman\PHPCheck\floats',
    'int'    => 'Datashaman\PHPCheck\choose',
    'mixed'  => 'Datashaman\PHPCheck\mixed',
    'string' => 'Datashaman\PHPCheck\strings',
];

/**
 * Generate an array of arguments for the callable function.
 *
 * <pre>
 * use function Datashaman\PHPCheck\arguments;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 *
 * function funcA(int $x, string $s) {
 * }
 *
 * print repr(generate(arguments('funcA'))) . PHP_EOL;
 *
 * \/**
 *  * @param string $s {@gen faker("username")}
 *  *\/
 * function funcB(string $s) {
 * }
 *
 * print repr(generate(arguments('funcB'))) . PHP_EOL;
 * </pre>
 *
 *
 */
function arguments(callable $f): Generator
{
    static $cache;

    if (!isset($cache)) {
        $cache = new Map();
    }

    if (!$cache->hasKey($f)) {
        $reflection = app('reflection');
        $function   = $reflection->reflect($f);

        $generators = [];

        foreach ($function->getParameters() as $param) {
            $tags = $reflection->getParamTags($param);

            if (\array_key_exists('gen', $tags)) {
                $generator = evalWithArgs($tags['gen']);
            } else {
                $paramType = $param->hasType() ? $param->getType() : null;
                $type      = $paramType ? $paramType->getName() : 'mixed';

                if (!\array_key_exists($type, TYPE_GENERATORS)) {
                    throw new Exception("No generator found for $type");
                }

                $generator = TYPE_GENERATORS[$type];
                $generator = $generator();
            }

            $generators[] = $generator;
        }

        $generator = makeGen(
            function (Random $r, int $n = null) use ($generators) {
                $arguments = [];

                foreach ($generators as $generator) {
                    $arguments[] = generate($generator, $r, $n);
                }

                return $arguments;
            }
        );

        $cache->put($f, $generator);
    }

    return $cache->get($f);
}

/**
 * Generate an array of mixed values.
 *
 * <pre>
 * use function Datashaman\PHPCheck\arrays;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 *
 * print repr(generate(arrays())) . PHP_EOL;
 * </pre>
 *
 */
function arrays(): Generator
{
    logExecution('mkGen', 'arrays');

    return listOf(mixed());
}

/**
 * Generate an ASCII character.
 *
 * <pre>
 * use function Datashaman\PHPCheck\ascii;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\strings;
 *
 * // Generate an ASCII character.
 * print repr(sample(ascii())) . PHP_EOL;
 *
 * // Generate an ASCII string.
 * print repr(sample(strings(ascii()))) . PHP_EOL;
 * </pre>
 *
 */
function ascii(): Generator
{
    logExecution('mkGen', 'ascii');

    return characters(0, 0x7F);
}

/**
 * Generate a boolean value.
 *
 * <pre>
 * use function Datashaman\PHPCheck\booleans;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 *
 * // Generate a boolean value with 50% chance true.
 * print repr(generate(booleans())) . PHP_EOL;
 *
 * // Generate a boolean value with 75% chance true.
 * print repr(generate(booleans(75))) . PHP_EOL;
 * </pre>
 *
 */
function booleans(int $chanceOfGettingTrue = 50): Generator
{
    logExecution('mkGen', 'booleans', 50);

    return makeGen(
        function (Random $r) use ($chanceOfGettingTrue) {
            return $r->random(1, 100) <= $chanceOfGettingTrue;
        }
    );
}

/**
 * Generate a character. The value is generated from all Unicode characters except control characters, surrogates and private ranges.
 *
 * <pre>
 * use function Datashaman\PHPCheck\characters;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(characters())) . PHP_EOL;
 * </pre>
 *
 * @param null|int|string $minChar the minimum character to be generated
 * @param null|int|string $maxChar the maximum character to be generated
 *
 */
function characters(
    $minChar = null,
    $maxChar = null
): Generator {
    logExecution('mkGen', 'characters', [$minChar, $maxChar]);

    if (null === $minChar) {
        $minCodepoint = UNICODE_MIN;
    } elseif (\is_string($minChar)) {
        $minCodepoint = $minChar === '' ? UNICODE_MIN : \mb_ord($minChar);
    } else {
        Assert::integer($minChar);
        $minCodepoint = $minChar;
    }

    if (null === $maxChar) {
        $maxCodepoint = UNICODE_MAX;
    } elseif (\is_string($maxChar)) {
        $maxCodepoint = \mb_ord($maxChar);
    } else {
        Assert::integer($minChar);
        $maxCodepoint = $maxChar;
    }

    Assert::lessThanEq($minCodepoint, $maxCodepoint);
    Assert::greaterThanEq($minCodepoint, UNICODE_MIN);
    Assert::lessThanEq($maxCodepoint, UNICODE_MAX);

    $codePoints = intervals(
        [
            [$minCodepoint, $maxCodepoint],
        ],
        UNICODE_EXCLUDE
    );

    return makeGen(
        function (Random $r) use ($codePoints) {
            $codepoint = generate($codePoints, $r);
            Assert::notNull($codepoint);

            $result = \mb_chr($codepoint);
            Assert::eq(\mb_strlen($result), 1);

            return $result;
        }
    );
}

/**
 * Generates a random element in the given inclusive range.
 *
 * This is another paragraph.
 *
 * <pre>
 * use function Datashaman\PHPCheck\choose;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(choose(0, 10))) . PHP_EOL;
 * print repr(sample(choose("a", "e"))) . PHP_EOL;
 * </pre>
 *
 * @param array|float|int|string $min The minimum element to generate. Can be an integer, float or a one character string. If it's an array, it must be a `[min, max]` pair.
 * @param float|int|string       $max The maximum element to generate. Can be an integer, float or a one character string.
 */
function choose($min = \PHP_INT_MIN, $max = \PHP_INT_MAX): Generator
{
    logExecution('mkGen', 'choose', [$min, $max]);

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

    return makeGen(
        function (Random $r) use ($min, $max, $strings) {
            if (\is_float($min) || \is_float($max)) {
                return generate(floats($min, $max), $r);
            }

            $value = $r->random($min, $max);
            Assert::integer($value);

            return $strings ? \mb_chr($value) : $value;
        }
    );
}

/**
 * Generate a value by type.
 *
 * <pre>
 * use function Datashaman\PHPCheck\chooseAny;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(chooseAny('float'))) . PHP_EOL;
 * </pre>
 *
 * @param string $type The type to be generated. Can be one of: `float`, `int`, `string`.
 *
 */
function chooseAny(string $type): Generator
{
    logExecution('mkGen', 'chooseAny', $type);

    switch ($type) {
        case 'float':
            return floats();
        case 'int':
            return choose();
        case 'string':
            return strings();
        default:
            throw new Exception('Unhandled type');
    }
}

/**
 * Generate datetimes, optionally with generated timezones.
 *
 * <pre>
 * use function Datashaman\PHPCheck\datetimes;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\timezones;
 *
 * print repr(sample(datetimes())) . PHP_EOL;
 *
 * print repr(sample(datetimes('2000-01-01', '2100-01-01', timezones()))) . PHP_EOL;
 * </pre>
 *
 * @param null|array|DateTime|string $min       The minimum datetime to be generated. If it's an array, it must a `[min, max]` pair.
 * @param null|DateTime|string       $max       the maximum datetime to be generated
 * @param null|Generator             $timezones Optional timezones generator. Default is naive datetimes.
 *
 */
function datetimes(
    $min = null,
    $max = null,
    Generator $timezones = null
): Generator {
    logExecution('mkGen', 'datetimes', [$min, $max]);

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

    return makeGen(
        function (Random $r) use ($min, $max, $timezones) {
            $timestamp = generate(choose(
                $min->getTimestamp(),
                $max->getTimestamp()
            ), $r);

            $datetime = new DateTime("@$timestamp");

            if ($timezones) {
                $timezone = new DateTimeZone(generate($timezones, $r));
                $datetime->setTimezone($timezone);
            }

            return $datetime;
        }
    );
}

/**
 * Generate dates.
 *
 * <pre>
 * use function Datashaman\PHPCheck\dates;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(dates('2000-01-01', '2300-01-01'))) . PHP_EOL;
 * </pre>
 *
 * @param array|DateTime|int|string $min The minimum date to be generated. If it's an array, it must be a `[min, max]` pair.
 * @param DateTime|int|string       $max the maximum date to be generated
 *
 */
function dates($min = null, $max = null): Generator
{
    logExecution('mkGen', 'dates', [$min, $max]);

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

    return makeGen(
        function (Random $r) use ($min, $max) {
            $datetime = generate(datetimes($min, $max), $r);

            $datetime->setTime(0, 0, 0);

            return $datetime;
        }
    );
}

/**
 * Generates one of the given values. The input list must be non-empty.
 *
 * <pre>
 * use function Datashaman\PHPCheck\elements;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\listOf1;
 *
 * print repr(generate(listOf1(elements(['abc', 123, 'u&me'])))) . PHP_EOL;
 * </pre>
 *
 * @param array $array the array to be generated from
 *
 */
function elements(array $array): Generator
{
    logExecution('mkGen', 'elements', $array);

    Assert::notEmpty($array);

    return makeGen(
        function (Random $r) use ($array) {
            $position = generate(choose(0, \count($array) - 1), $r);

            return $array[$position];
        }
    );
}

/**
 * Generates a value from the Faker factory.
 *
 * <pre>
 * use function Datashaman\PHPCheck\faker;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 *
 * print repr(generate(faker("creditCardDetails"))) . PHP_EOL;
 * print repr(generate(faker("email"))) . PHP_EOL;
 * print repr(generate(faker("imageUrl", 400, 300, "cats"))) . PHP_EOL;
 * </pre>
 *
 * @param array $args,... First argument is the factory property or method name. If there is more than 1 argument, it's treated as a method call. If if there is 1 argument, it's treated as a property.
 *
 */
function faker(...$args): Generator
{
    logExecution('mkGen', 'faker', $args);

    Assert::greaterThanEq(\count($args), 1);

    $attr  = \array_shift($args);
    $faker = app('faker');

    return makeGen(
        function () use ($attr, $args, $faker) {
            if (\count($args)) {
                return \call_user_func([$faker, $attr], ...$args);
            }

            return $faker->{$attr};
        }
    );
}

/**
 * Generate floats optionally within a specific range.
 *
 * <pre>
 * use function Datashaman\PHPCheck\floats;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 *
 * print repr(generate(floats(-1000, 1000))) . PHP_EOL;
 * </pre>
 *
 * @param float $min the minimum float value to generate
 * @param float $max the maximum float value to generate
 *
 */
function floats(float $min = \PHP_FLOAT_MIN, float $max = \PHP_FLOAT_MAX): Generator
{
    logExecution('mkGen', 'floats', [$min, $max]);

    Assert::lessThanEq($min, $max);

    return makeGen(
        function () use ($min, $max) {
            return \max(
                $min,
                \min(
                    $min + \mt_rand() / \mt_getrandmax() * ($max - $min),
                    $max
                )
            );
        }
    );
}

/**
 * Chooses one of the given generators, with a weighted random distribution. The input list must be non-empty.
 *
 * <pre>
 * use function Datashaman\PHPCheck\faker;
 * use function Datashaman\PHPCheck\frequency;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(frequency([
 *     [1, faker("email")],
 *     [4, faker("lastName")],
 * ]))) . PHP_EOL;
 * </pre>
 *
 * @param array $frequencies a weighted list consisting of pairs of `[weight, generator]`
 *
 */
function frequency(array $frequencies): Generator
{
    logExecution('mkGen', 'frequencies', $frequencies);

    Assert::notEmpty($frequencies);

    $map = new Map();

    foreach ($frequencies as $frequency) {
        [$weighted, $gen] = $frequency;
        $map->put($gen, $weighted);
    }

    return makeGen(
        function (Random $r) use ($map) {
            $count = $map->count();

            if ($count <= 1) {
                return $map->keys()->first();
            }

            $sum = $map->sum();

            if ($sum < 1) {
                throw new Exception('Negative or all-zero weights not allowed');
            }

            $targetWeight = $r->random(1, $sum);

            foreach ($map as $key => $weight) {
                if ($weight < 0) {
                    throw new Exception('Negative weights not allowed');
                }

                $targetWeight -= $weight;

                if ($targetWeight <= 0) {
                    return generate($key, $r);
                }
            }
        }
    );
}

/**
 * Takes a list of elements of increasing size, and chooses among an initial segment of the list. The size of this initial segment increases with the size parameter. The input list must be non-empty.
 *
 * <pre>
 * use function Datashaman\PHPCheck\growingElements;
 * use function Datashaman\PHPCheck\listOf;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(listOf(growingElements([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20])))) . PHP_EOL;
 * </pre>
 *
 * @param array $array the array containing the elements to be selected
 *
 */
function growingElements(array $array): Generator
{
    return makeGen(
        function (Random $r, $n) use ($array) {
            $k = \count($array);
            $mx = 100;
            $log_ = function ($value) {
                return \round(\log($value));
            };
            $n = (int) (($log_($n) + 1) * \floor($k / $log_($mx)));

            return generate(elements(\array_slice($array, 0, \max(1, $n))), $r);
        }
    );
}

/**
 * Generate an element within a set of intervals, and excluding another set of intervals.
 *
 * Intervals are defined as a list of `[min, max]` pairs, for example: `[[1, 3], [4, 10], [11, 40]]`.
 *
 * <pre>
 * use function Datashaman\PHPCheck\intervals;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(intervals([[1, 10]], [[1, 5]]))) . PHP_EOL;
 * </pre>
 *
 * @param array $include an array of intervals to include in selecting the value
 * @param array $exclude an array of intervals to exclude from the selection
 *
 */
function intervals(
    array $include = [[\PHP_INT_MIN, \PHP_INT_MAX]],
    array $exclude = []
): Generator {
    logExecution('mkGen', 'intervals', \compact('include', 'exclude'));

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

    return makeGen(
        function (Random $r) use ($exclude, $intervals, $max) {
            $gen = choose(0, $max);

            while (true) {
                $integer = generate($gen, $r);

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

/**
 * Generates a list of random length. The maximum length depends on the size parameter.
 *
 * <pre>
 * use function Datashaman\PHPCheck\faker;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\listOf;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\resize;
 * use function Datashaman\PHPCheck\strings;
 *
 * print repr(generate(listOf(strings(faker("emoji"))))) . PHP_EOL;
 *
 * print repr(generate(resize(2, listOf(faker("ipv4"))))) . PHP_EOL;
 * </pre>
 *
 * @param Generator $gen the generator that creates the values
 *
 */
function listOf(Generator $gen): Generator
{
    logExecution('mkGen', 'listOf', $gen);

    return makeGen(
        function (Random $r, $n) use ($gen) {
            $newSize = generate(choose(0, $n), $r);

            return generate(vectorOf($newSize, $gen), $r);
        }
    );
}

/**
 * Generates a non-empty list of random length. The maximum length depends on the size parameter.
 *
 * <pre>
 * use function Datashaman\PHPCheck\faker;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\listOf1;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\resize;
 *
 * print repr(generate(listOf1(faker("creditCardNumber")))) . PHP_EOL;
 *
 * print repr(generate(resize(2, listOf1(faker("emoji"))))) . PHP_EOL;
 * </pre>
 *
 * @param Generator $gen the generator that creates the values
 *
 */
function listOf1(Generator $gen): Generator
{
    logExecution('mkGen', 'listOf1', $gen);

    return makeGen(
        function (Random $r, $n) use ($gen) {
            $newSize = generate(choose(1, \max(1, $n)), $r);

            return generate(vectorOf($newSize, $gen), $r);
        }
    );
}

/**
 * Randomly generates from one of the following generators:
 * * [strings()](#strings)
 * * [choose()](#choose)
 * * [booleans()](#booleans)
 * * [dates()](#dates)
 * * [datetimes()](#datetimes)
 * * [listOf(strings())](#listOf)
 * * [listOf(choose())](#listOf)
 *
 * <pre>
 * use function Datashaman\PHPCheck\mixed;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(mixed())) . PHP_EOL;
 * </pre>
 *
 */
function mixed(): Generator
{
    logExecution('mkGen', 'mixed');

    return oneof(
        [
            strings(),
            choose(),
            booleans(),
            dates(),
            datetimes(),
            listOf(strings()),
            listOf(choose()),
        ]
    );
}

/**
 * Randomly uses one of the given generators. The input list must be non-empty.
 *
 * <pre>
 * use function Datashaman\PHPCheck\faker;
 * use function Datashaman\PHPCheck\oneof;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 *
 * print repr(sample(oneof(
 *     [
 *         faker("email"),
 *         faker("e164PhoneNumber")
 *     ]
 * ))) . PHP_EOL;
 * </pre>
 *
 * @param array $gens the list of generators to be chosen from
 *
 */
function oneof(array $gens): Generator
{
    logExecution('mkGen', 'oneof', $gens);

    return makeGen(
        function (Random $r) use ($gens) {
            $choice = generate(choose(0, \count($gens) - 1), $r);

            return generate($gens[$choice], $r);
        }
    );
}

/**
 * Overrides the size parameter. Returns a generator which uses the given size instead of the runtime-size parameter.
 *
 * <pre>
 * use function Datashaman\PHPCheck\faker;
 * use function Datashaman\PHPCheck\listOf;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\resize;
 *
 * print repr(sample(resize(3, listOf(faker("firstname"))))) . PHP_EOL;
 * </pre>
 *
 * @param int       $n   the size to be used by the generator
 * @param Generator $gen the generator that creates the values
 *
 */
function resize(int $n, Generator $gen): Generator
{
    logExecution('mkGen', 'resize', [$n, $gen]);

    return makeGen(
        function (Random $r) use ($n, $gen) {
            return generate($gen, $r, $n);
        }
    );
}

/**
 * Adjust the size parameter, by transforming it with the given function.
 *
 * <pre>
 * use function Datashaman\PHPCheck\ascii;
 * use function Datashaman\PHPCheck\listOf;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\scale;
 * use function Datashaman\PHPCheck\strings;
 *
 * print repr(sample(scale(function ($n) {
 *     return $n / 10;
 * }, listOf(strings(ascii()))))) . PHP_EOL;
 * </pre>
 *
 * @param callable  $f   the transform function that scales the size
 * @param Generator $gen the generator who's size will be scaled
 *
 */
function scale(callable $f, Generator $gen): Generator
{
    logExecution('mkGen', 'scale', ['callable', $gen]);

    return makeGen(
        function (Random $r, $n) use ($f, $gen) {
            $newSize = (int) \call_user_func($f, $n);

            return generate($gen, $r, $newSize);
        }
    );
}

/**
 * Generate strings, optionally from a specific character generator.
 *
 * <pre>
 * use function Datashaman\PHPCheck\ascii;
 * use function Datashaman\PHPCheck\characters;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\strings;
 *
 * print repr(generate(strings())) . PHP_EOL;
 * print repr(generate(strings(ascii()))) . PHP_EOL;
 * print repr(generate(strings(characters('a', 'e')))) . PHP_EOL;
 * </pre>
 *
 *
 */
function strings(Generator $characters = null): Generator
{
    logExecution('mkGen', 'strings');

    if (null === $characters) {
        $characters = characters();
    }

    return makeGen(
        function (Random $r, int $n) use ($characters) {
            $newSize = generate(choose(0, $n), $r);
            $result = '';

            while (\mb_strlen($result) < $newSize) {
                $result .= generate($characters, $r);
            }

            return \mb_substr($result, 0, $newSize);
        }
    );
}

/**
 * Generates a value that satisfies a predicate.
 *
 * <pre>
 * use function Datashaman\PHPCheck\choose;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\suchThat;
 *
 * print repr(sample(suchThat(choose(0, 100), function ($value) {
 *     return $value < 50;
 * }))) . PHP_EOL;
 * </pre>
 *
 * @param Generator $gen the generator that creates the values
 * @param callable  $f   the predicate function that must be satisfied
 *
 */
function suchThat(Generator $gen, callable $f): Generator
{
    logExecution('mkGen', 'suchThat', [$gen, 'callable']);

    return makeGen(
        function (Random $r, $n) use ($gen, $f) {
            $mx = generate(suchThatMaybe($gen, $f), $r);

            if ($mx->isJust()) {
                return $mx->value();
            }

            if ($mx->isNothing()) {
                return generate(suchThat($gen, $f), $r, $n + 1);
            }

            throw new Exception('This should not be possible');
        }
    );
}

/**
 * Generates a value for which the given function returns a Just, and then applies the function.
 *
 * The callable must return a Maybe object.
 *
 * <pre>
 * use function Datashaman\PHPCheck\choose;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\suchThatMap;
 * use Datashaman\PHPCheck\Types\Just;
 *
 * print repr(sample(suchThatMap(choose(0, 5), function ($value) {
 *     return new Just($value + 100);
 * }))) . PHP_EOL;
 * </pre>
 *
 * @param Generator $gen the generator that creates the values
 * @param callable  $f   the map function
 *
 */
function suchThatMap(Generator $gen, callable $f): Generator
{
    logExecution('mkGen', 'suchThatMap', [$gen, 'callable']);

    return makeGen(
        function (Random $r) use ($gen, $f) {
            $value = generate(suchThat($gen, $f), $r);

            $result = \call_user_func($f, $value);

            if (!$result instanceof Maybe) {
                throw new Exception('Callable must return Maybe type');
            }

            return $result->value();
        }
    );
}

/**
 * Tries to generate a value that satisfies a predicate. If it fails to do so after enough attempts, returns Nothing.
 *
 * <pre>
 * use function Datashaman\PHPCheck\choose;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\suchThatMaybe;
 *
 * print repr(sample(suchThatMaybe(choose(0, 100), function ($value) {
 *     return $value < 99;
 * }))) . PHP_EOL;
 *
 * print repr(sample(suchThatMaybe(choose(0, 100), function ($value) {
 *     return $value > 99;
 * }))) . PHP_EOL;
 * </pre>
 *
 * @param Generator $gen the generator that creates the values
 * @param callable  $f   the predicate function that must be satisfied
 *
 */
function suchThatMaybe(Generator $gen, callable $f): Generator
{
    logExecution('mkGen', 'suchThatMaybe', [$gen, 'callable']);

    $try = function ($current, $maximum, $r) use ($f, $gen, &$try) {
        if ($current > $maximum) {
            return new Nothing();
        }

        $item = generate($gen, $r, $current);

        if (\call_user_func($f, $item)) {
            return new Just($item);
        }

        return \call_user_func($try, $current + 1, $maximum, $r);
    };

    return makeGen(
        function (Random $r, $n) use ($try) {
            return $try($n, $n * 2, $r);
        }
    );
}

/**
 * Generate a timezone.
 *
 * <pre>
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\timezones;
 *
 * print repr(generate(timezones())) . PHP_EOL;
 * </pre>
 *
 */
function timezones(): Generator
{
    logExecution('mkGen', 'timezones');

    $zones     = \timezone_identifiers_list();
    $positions = choose(0, \count($zones) - 1);

    return makeGen(
        function (Random $r) use ($positions, $zones) {
            $position = generate($positions, $r);

            return $zones[$position];
        }
    );
}

/**
 * Modifies a generator using an integer seed so it will always produce the same result.
 *
 * <pre>
 * use function Datashaman\PHPCheck\ascii;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\strings;
 * use function Datashaman\PHPCheck\variant;
 *
 * print repr(sample(variant("123", strings(ascii())))) . PHP_EOL;
 * </pre>
 *
 * @param string    $seed the seed to be used by the generator
 * @param Generator $gen  the generator to be seeded
 *
 */
function variant(string $seed, Generator $gen): Generator
{
    logExecution('mkGen', 'variant', [$seed, $gen]);

    return makeGen(
        function () use ($seed, $gen) {
            return generate($gen, new Random($seed));
        }
    );
}

/**
 * Generates a list of the given length.
 *
 * <pre>
 * use function Datashaman\PHPCheck\choose;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\repr;
 * use function Datashaman\PHPCheck\vectorOf;
 *
 * print repr(generate(vectorOf(5, choose(0, 10)))) . PHP_EOL;
 * </pre>
 *
 * @param int       $n   the length of the list to be generated
 * @param Generator $gen the generator that produces the values
 *
 */
function vectorOf(int $n, Generator $gen): Generator
{
    logExecution('mkGen', 'vectorOf', [$n, $gen]);

    return makeGen(
        function (Random $r) use ($n, $gen) {
            $result = [];
            $count = 0;

            while (\count($result) < $n) {
                $result[] = generate($gen, $r);
            }

            return $result;
        }
    );
}
