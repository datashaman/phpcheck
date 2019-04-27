<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Traits;

use Ds\Map;

trait LogTrait
{
    public function repr($value)
    {
        if ($value instanceof Map) {
            return \get_class($value) . ' {#' . \spl_object_id($value) . '}';
        }

        if (\is_string($value)) {
            return '"' . $value . '"';
        }

        if (\is_numeric($value)) {
            return $value;
        }

        if (\is_array($value)) {
            if (\count($value)) {
                $keys = \array_keys($value);

                if (\is_int($keys[0])) {
                    return '[' . \implode(', ', \array_map(
                        function ($item) use ($value) {
                            return $this->repr($item);
                        },
                        $value
                    )) . ']';
                } else {
                    return '[' . \implode(', ', \array_map(
                        function ($key) use ($value) {
                            return "$key=" . $this->repr($value[$key]);
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

    public function logExecution($subject, $method, $values = null): void
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
                    $parts[] = $this->repr($value);
                } else {
                    $parts[] = $key . '=' . $this->repr($value);
                }
            }
        } else {
            $parts = null === $values
                ? []
                : [$this->repr($values)];
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
}
