<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Checks;

use DateTime;

class GeneratorCheck
{
    /**
     * @param string $char {@gen characters()}
     */
    public function checkCharacters(string $char): bool
    {
        $ord = \mb_ord($char);

        return \mb_strlen($char) === 1
            && $ord >= \Datashaman\PHPCheck\UNICODE_MIN
            && $ord <= \Datashaman\PHPCheck\UNICODE_MAX
            && !\array_filter(
                \Datashaman\PHPCheck\UNICODE_EXCLUDE,
                function ($interval) use ($ord) {
                    return $ord >= $interval[0] && $ord <= $interval[1];
                }
            );
    }

    public function checkStrings(string $string): bool
    {
        return \mb_strlen($string) < 100;
    }

    /**
     * @param string $string {@gen ascii()}
     */
    public function checkAscii(string $string): bool
    {
        return \mb_strlen($string) <= 30
            && (bool) \array_filter(
                $this->mbSplit($string),
                function ($character) {
                    $ord = \mb_ord($character);

                    return $ord >= 0 && $ord <= 0x7F;
                }
            );
    }

    /**
     * @coverTable "Values" [[true, 49], [false, 49]]
     * @maxSuccess 10000
     * @tabulate "Values" [$bin]
     */
    public function checkBooleans(bool $bin): bool
    {
        return true;
    }

    /**
     * @param bool $bin {@gen booleans(75)}
     *
     * @coverTable "Values" [[true, 74], [false, 24]]
     * @maxSuccess 10000
     * @tabulate "Values" [$bin]
     */
    public function checkBooleansWithPercentage(bool $bin): bool
    {
        return true;
    }

    /**
     * @param string $char {@gen characters(32, 126)}
     */
    public function checkCharactersWithNumbers(string $char): bool
    {
        $ord = \mb_ord($char);

        return $ord >= 32 && $ord <= 126;
    }

    /**
     * @param string $char {@gen characters(" ", "~")}
     */
    public function checkCharactersWithStrings(string $char): bool
    {
        return $char >= ' ' && $char <= '~';
    }

    /**
     * @param int $int {@gen elements([1,2,3])}
     */
    public function checkChoose(int $int): bool
    {
        return $int >= 1 && $int <= 3;
    }

    /**
     * @param DateTime $value {@gen dates()}
     */
    public function checkDates(DateTime $value): bool
    {
        return $value->format('H:i:s') === '00:00:00';
    }

    /**
     * @param DateTime $value {@gen datetimes()}
     */
    public function checkNaiveDateTimes(DateTime $value): bool
    {
        return $value->format('H:i:s') !== '00:00:00'
            && $value->getOffset() === 0;
    }

    /**
     * @param DateTime $value {@gen datetimes("0001-01-01", "9999-12-31", timezones())}
     */
    public function checkDateTimesWithTimezones(DateTime $value): bool
    {
        return $value->format('H:i:s') !== '00:00:00'
            && $value->getTimezone() !== null;
    }

    /**
     * @maxSuccess 5
     */
    public function checkMaxSuccess(): bool
    {
        static $checks = 0;
        $checks++;

        return $checks <= 5;
    }

    /**
     * @param float $float {@gen floats(0, 5)}
     */
    public function checkFloats(float $float): bool
    {
        return $float >= 0 && $float <= 5;
    }

    /**
     * @param array $array {@gen listOf(choose(0, 10))}
     */
    public function checkListOfInt(array $array): bool
    {
        return !\count($array)
            || (bool) \array_filter(
                $array,
                function ($item) {
                    return \is_int($item) && $item >= 0 && $item <= 10;
                }
            );
    }

    /**
     * @param mixed $value {@gen oneof([choose(0, 10), choose("a", "z")])}
     */
    public function checkOneof($value): bool
    {
        return $value >= 'a' && $value <= 'z'
            || $value >= 0 && $value <= 10;
    }

    /**
     * @param array $list {@gen vectorOf(5, choose(0, 10))}
     */
    public function checkVectorOfInts(array $list): bool
    {
        return \count($list) === 5
            && (bool) \array_filter(
                $list,
                function ($item) {
                    return \is_int($item) && $item >= 0 && $item <= 10;
                }
            );
    }

    /**
     * @param string $str {@gen faker("email")}
     */
    public function checkFakerWithoutArgs(string $str): bool
    {
        return \filter_var($str, \FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param int $int {@gen faker("numberBetween", 5, 5)}
     */
    public function checkFakerWithArgs(int $int): bool
    {
        return $int === 5;
    }

    /**
     * @param int $int {@gen variant("123", choose(0, 1000000))}
     */
    public function checkVariant(int $int): bool
    {
        return $int === 857674;
    }

    /**
     * @param string $zone {@gen timezones()}
     */
    public function checkTimezones(string $zone): bool
    {
        return \in_array($zone, \timezone_identifiers_list());
    }

    /**
     * @param int $i {@gen choose(1, 5)}
     *
     * @coverTable "Values" [[1, 18], [2, 18], [3, 18], [4, 18], [5, 18]]
     * @maxSuccess 10000
     * @tabulate "Values" [$i]
     */
    public function checkTabulate(int $i): bool
    {
        return $i >= 1 && $i <= 5;
    }

    protected function mbSplit(string $str)
    {
        if (($arr = \preg_split('/(?<!^)(?!$)/u', $str)) !== false) {
            return \array_filter(
                $arr,
                function ($char) {
                    return $char !== '';
                }
            );
        }

        return [];
    }
}
