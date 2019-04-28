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

use Ds\Map;
use Generator;
use Symfony\Component\Console\Output\StreamOutput;

\define('LOG', 'phpcheck.log');

/**
 * Helper function for interacting with
 * the dependency container.
 *
 * <pre>
 * use function Datashaman\PHPCheck\app;
 *
 * app('thing', function ($c) {
 *     return 'THING';
 * });
 *
 * echo app('thing') . "\n";
 * </pre>
 *
 * @param string        $name Dependency name
 * @param null|callable $f    Callable to create the dependency. If this is null, the dependency is returned.
 * @nodocs
 *
 * @return mixed
 */
function app($name, callable $f = null)
{
    static $container;

    if (!isset($container)) {
        $container = new \Pimple\Container();
    }

    if (null === $f) {
        return $container[$name];
    }

    $container[$name] = $f;
}

/**
 * Normalize the args coming back from a yield inside
 * the generator.
 *
 * @param mixed $args Arguments received from a yield call.
 * @nodocs
 *
 * @return array
 */
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

/**
 * Evaluate an expression of PHP code embedded in a doc block.
 *
 * Yes, I know it's eval. But it's developer input, not user input
 * and it's run in a testing context only.
 *
 * <pre>
 * use function Datashaman\PHPCheck\evalWithArgs;
 *
 * var_dump(evalWithArgs('strtoupper("Hi $name!")', ['name' => 'Bob']));
 * </pre>
 *
 * @param string $expression PHP string expression to be evaluated. Must not include semi-colon.
 * @param array $args Local arguments defined while the expression is evaluated.
 * @nodocs
 *
 * @return mixed
 */
function evalWithArgs(string $expression, $args = [])
{
    \extract($args);
    $expression = "namespace Datashaman\PHPCheck; return $expression;";

    return eval($expression);
}

/**
 * Run a generator.
 *
 * <pre>
 * use function Datashaman\PHPCheck\ascii;
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\strings;
 * use function Datashaman\PHPCheck\choose;
 *
 * // Generate an integer from 0 to 10.
 * echo generate(choose(0, 10)) . "\n";
 *
 * // Generate an ASCII string.
 * echo generate(strings(ascii())) . "\n";
 * </pre>
 * @param Generator $gen
 * @param null|Random $r
 * @param null|int $n
 *
 * @return mixed
 */
function generate(
    Generator $gen,
    Random $r = null,
    int $n = null
) {
    return $gen->send([$r, $n]);
}

/**
 * @nodocs
 */
function logExecution($subject, $method, $values = null): void
{
    static $counter = 0;
    static $lastArgs;

    if (\getenv('APP_ENV') !== 'dev') {
        return;
    }

    $parts = [];

    if (\is_array($values)) {
        foreach ($values as $key => $value) {
            if (\is_int($key)) {
                $parts[] = repr($value);
            } else {
                $parts[] = $key . '=' . repr($value);
            }
        }
    } else {
        $parts = null === $values
            ? []
            : [repr($values)];
    }

    if (isset($lastArgs)) {
        if (\func_get_args() == $lastArgs) {
            $counter++;

            return;
        }
        $counter++;
        $times = $counter > 1 ? "$counter times" : '';
        \file_put_contents(LOG, $subject . ' ' . $method . '(' . ($parts ? \implode(', ', $parts) : '') . ") $times\n", \FILE_APPEND);
        $counter = 0;
    }

    $lastArgs = \func_get_args();
}

/**
 * Make a generator from a callable function.
 *
 * <pre>
 * use function Datashaman\PHPCheck\generate;
 * use function Datashaman\PHPCheck\makeGen;
 * use function Datashaman\PHPCheck\sample;
 * use Datashaman\PHPCheck\Random;
 *
 * // Make a generator that returns a random value from 0 to 9.
 * $genA = makeGen(function (Random $r, int $n = null) {
 *     return $r->random(0, 9);
 * });
 *
 * // Generate some integers.
 * var_dump(sample($genA));
 *
 * // Make another generator that uses $genA to return a character.
 * // The Random object $r is passed to the generate call.
 * $genB = makeGen(function (Random $r, int $n = null) use ($genA) {
 *     $index = generate($genA, $r);
 *
 *     return chr($index + 97);
 * });
 *
 * // Generate some characters.
 * var_dump(sample($genB));
 * </pre>
 *
 * @param callable $f A callable function that returns a value, should accept (Random $r = null, int $n = null), and should pass $r into any generate calls within its body.
 *
 * @return Generator
 */
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

/**
 * Checks that a so-called property function (or check) holds true for all arguments given.
 *
 * A property function (or check) must return a boolean result.
 *
 * <pre>
 * use function Datashaman\PHPCheck\quickCheck;
 *
 * /**
 *  * @param string $email {@gen faker('email')}
 *  *\/
 * function checkEmails(string $email) {
 *     return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
 * }
 *
 * quickCheck('checkEmails');
 *
 * /**
 *  * @param int $i {@gen choose(1, 10)}
 *  *\/
 * function checkIntegers(int $i) {
 *     return is_int($i) && $i >= 1 && $i <= 10;
 * }
 *
 * quickCheck('checkIntegers');
 * </pre>
 *
 * @param callable $f The property function (or check).
 * @param null|resource $output Stream for the quickCheck output. Defaults to `stdout`.
 *
 * @return Result
 */
function quickCheck(callable $f, resource $output = null)
{
    $stdout = false;

    if (null === $output) {
        $stdout = true;
        $output = fopen('php://temp', 'w');
    }

    $args = new Args();
    $args->subject = $f;
    $args->output = new StreamOutput($output);

    app('runner')->execute($args);

    if ($stdout) {
        rewind($output);
        echo stream_get_contents($output);
        fclose($output);
    }
}

/**
 * Return a simple string representation of the value for display and logging.
 *
 * <pre>
 * use function Datashaman\PHPCheck\repr;
 * use Ds\Map;
 *
 * var_dump(repr([1, 2, 3]));
 * var_dump(repr(['a' => 'A', 'b' => 'B', 'c' => 'C']));
 * var_dump(repr(new Ds\Map(['a' => 'A', 'b' => 'B', 'c' => 'C'])));
 * var_dump(repr("string"));
 * var_dump(repr(100));
 * var_dump(repr(new DateTime()));
 * </pre>
 *
 * @param mixed $value The value to represent.
 * @nodocs
 *
 * @return string
 */
function repr($value)
{
    if ($value instanceof Map) {
        return \get_class($value) . ' {#' . \spl_object_id($value) . '}';
    }

    if (\is_string($value)) {
        return '"' . $value . '"';
    }

    if ($value === PHP_INT_MIN) {
        return 'PHP_INT_MIN';
    }

    if ($value === PHP_INT_MAX) {
        return 'PHP_INT_MAX';
    }

    if (\is_numeric($value)) {
        return $value;
    }

    if (\is_array($value)) {
        if (\count($value)) {
            $keys = \array_keys($value);

            if (\is_int($keys[0])) {
                return '[' . \implode(', ', \array_map(
                    function ($item) {
                        return repr($item);
                    },
                    $value
                )) . ']';
            } else {
                return '[' . \implode(', ', \array_map(
                    function ($key) use ($value) {
                        return "$key=" . repr($value[$key]);
                    },
                    $keys
                )) . ']';
            }
        } else {
            return '[]';
        }
    }

    return \json_encode($value);
}

/**
 * Generates some example values in increasingly random size.
 *
 * <pre>
 * use function Datashaman\PHPCheck\ascii;
 * use function Datashaman\PHPCheck\sample;
 * use function Datashaman\PHPCheck\strings;
 *
 * var_dump(sample(strings(ascii())));
 * </pre>
 *
 * @param Generator $gen The generator that creates the values.
 *
 * @return array
 */
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
