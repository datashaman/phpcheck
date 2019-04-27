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

function makeGen(callable $f): Generator
{
    $func = function () use ($f) {
        $args = yield;

        while (true) {
            [$r, $n] = checkArgs($args);
            $args    = yield $f($r, $n);
        }
    };

    $gen = $func();
    $gen->next();

    return $gen;
}

function checkArgs($args)
{
    $args = $args ?: [];

    $args    = \array_pad($args, 2, null);
    [$r, $n] = $args;

    if (!$r) {
        $r = app('random');
    }

    if (null === $n) {
        $n = 30;
    }

    return [$r, $n];
}

function generate($gen, Random $r = null, int $n = null)
{
    return $gen->send([$r, $n]);
}

function arguments(callable $callable): Generator
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
            function () use ($generators) {
                $arguments = [];

                foreach ($generators as $generator) {
                    $arguments[] = generate($generator);
                }

                return $arguments;
            }
        );

        $cache->put($callable, $generator);
    }

    return $cache->get($callable);
}

function arrays(): Generator
{
    logExecution('mkGen', 'arrays');

    return listOf(mixed());
}

function ascii(): Generator
{
    logExecution('mkGen', 'ascii');

    return characters(0, 0x7F);
}

function booleans(int $chanceOfGettingTrue = 50): Generator
{
    logExecution('mkGen', 'booleans', 50);

    return makeGen(
        function (Random $r) use ($chanceOfGettingTrue) {
            return $r->random(1, 100) <= $chanceOfGettingTrue;
        }
    );
}

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

function floats(float $min, float $max): Generator
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

function frequency(array $frequencies): Generator
{
    logExecution('mkGen', 'frequencies', $frequencies);

    Assert::notEmpty($frequencies);

    $map = new Map();

    foreach ($frequencies as $frequency) {
        [$weighted, $gen] = $frequency;
        $map->put($gen, $weighted);
    }

    return pick($map);
}

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

function pick(Map $weighted): Generator
{
    logExecution('mkGen', 'pick', $weighted);

    return makeGen(
        function (Random $r) use ($weighted) {
            return $r->arrayWeightRand($weighted);
        }
    );
}

function resize(int $n, Generator $gen): Generator
{
    logExecution('mkGen', 'resize', [$n, $gen]);

    return makeGen(
        function (Random $r) use ($n, $gen) {
            return generate($gen, $r, $n);
        }
    );
}

function sample(Generator $gen): array
{
    logExecution('mkGen', 'sample', $gen);

    $sizes = \range(0, 20, 2);

    return \array_map(
        function ($n) use ($gen) {
            return generate($gen, null, $n);
        },
        $sizes
    );
}

function scale(callable $f, Generator $gen): Generator
{
    logExecution('mkGen', 'scale', ['callable', $gen]);

    return makeGen(
        function (Random $r, $n) use ($f, $gen) {
            $newSize = \call_user_func($f, $n);

            return generate($gen, $r, $newSize);
        }
    );
}

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
        function (Random $r, $n) use ($gen, $try) {
            return $try($n, $n * 2, $r);
        }
    );
}

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

function variant(int $seed, Generator $gen): Generator
{
    logExecution('mkGen', 'variant', [$seed, $gen]);

    return makeGen(
        function () use ($seed, $gen) {
            return generate($gen, new Random($seed));
        }
    );
}

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
