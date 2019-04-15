<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Checks;

use Datashaman\PHPCheck\ArgumentFactory;
use Datashaman\PHPCheck\Check;
use Datashaman\PHPCheck\Gen;
use Generator;
use ReflectionFunction;
use Webmozart\Assert\Assert;

class GenCheck extends Check
{
    const ITERATIONS = 100;

    protected function mbSplit(string $string)
    {
        if (($arr = preg_split('/(?<!^)(?!$)/u', $string)) !== false) {
            return array_filter($arr);
        }

        return [];
    }

    /**
     * @param string $c {@gen characters}
     */
    public function checkCharacters(string $c)
    {
        Assert::eq(1, mb_strlen($c));
        $ord = mb_ord($c);
        Assert::greaterThanEq($ord, Gen::MIN_UNICODE, $ord);
        Assert::lessThanEq($ord, Gen::MAX_UNICODE);
        foreach (Gen::EXCLUDE_UNICODE as $interval) {
            Assert::false($ord >= $interval[0] && $ord <= $interval[1]);
        }
    }

    public function checkStrings(string $string)
    {
        Assert::true(mb_strlen($string) <= 30);
    }

    /**
     * @param string $string {@gen ascii}
     */
    public function checkAscii(string $string)
    {
        Assert::true(mb_strlen($string) <= 30);

        foreach ($this->mbSplit($string) as $character) {
            Assert::range(ord($character), 0, 0x7F);
        }
    }

    /**
     * @iterates
     */
    public function checkBooleans()
    {
        $counts = [
            false => 0,
            true => 0,
        ];

        $func = function (bool $b) use (&$counts) {
            $counts[$b]++;
        };

        $this->runner->iterate(
            $func,
            10000
        );

        $total = $counts[false] + $counts[true];
        $percentageTrue = $counts[true] / $total * 100;

        Assert::true($percentageTrue >= 45 && $percentageTrue <= 55);
    }

    /**
     * @iterates
     */
    public function checkBooleansWithPercentage()
    {
        $counts = [
            false => 0,
            true => 0,
        ];

        /**
         * @param bool $b {@gen booleans:[75]}
         */
        $func = function (bool $b) use (&$counts) {
            $counts[$b]++;
        };

        $this->runner->iterate(
            $func,
            10000
        );

        $total = $counts[false] + $counts[true];
        $percentageTrue = $counts[true] / $total * 100;

        Assert::true($percentageTrue >= 70 && $percentageTrue <= 80);
    }

    /**
     * @param string $c {@gen characters:[32,126]}
     */
    public function checkCharactersWithNumbers(string $c)
    {
        $ord = mb_ord($c);
        Assert::greaterThanEq($ord, 32);
        Assert::lessThanEq($ord, 126);
    }

    /**
     * @param string $c {@gen characters:[" ","~"]}
     */
    public function checkCharactersWithStrings(string $c)
    {
        $ord = mb_ord($c);
        Assert::greaterThanEq($ord, 32);
        Assert::lessThanEq($ord, 126);
    }

    /**
     * @param int $value {@gen choose:[[1,2,3]]}
     */
    public function checkChoose(int $value)
    {
        Assert::range($value, 1, 3);
    }

    /**
     * @iterations 5
     */
    public function checkIterations()
    {
        static $iterations = 0;
        $iterations++;
        Assert::lessThanEq($iterations, 5);
    }

    /**
     * @param float $f {@gen floats:[0,5]}
     */
    public function checkFloats(float $f)
    {
        Assert::greaterThanEq($f, 0);
        Assert::lessThanEq($f, 5);

        if (preg_match('/\.([0-9]*)$/', (string) $f, $match)) {
            Assert::lessThanEq(strlen($match[1]), 4);
        }
    }

    /**
     * @param float $f {@gen floats:[0,5,{@gen integers:[4,4]}]}
     */
    public function checkFloatsWithDecimalGen(float $f)
    {
        Assert::greaterThanEq($f, 0);
        Assert::lessThanEq($f, 5);

        if (preg_match('/\.([0-9]*)$/', (string) $f, $match)) {
            Assert::lessThanEq(strlen($match[1]), 4);
        }
    }

    /**
     * @param string $s {@gen strings:[{@gen integers:[5,30]}]}
     */
    public function checkStringsWithMinMax(string $s)
    {
        $count = mb_strlen($s);
        Assert::lessThanEq($count, 30);
        Assert::greaterThanEq($count, 5);
    }

    /**
     * @param array $list {@gen listOf:[{@gen integers:[0,10]},{@gen integers:[5,5]}]}
     */
    public function checkListOfInts(array $list)
    {
        Assert::count($list, 5);

        foreach ($list as $value) {
            Assert::integer($value);
        }
    }

    /**
     * @param string $str {@gen faker:["email"]}
     */
    public function checkFakerWithoutArgs(string $str)
    {
        Assert::true(filter_var($str, FILTER_VALIDATE_EMAIL) !== false, 'should produce an email');
    }

    /**
     * @param int $n {@gen faker:["numberBetween",5,5]}
     */
    public function checkFakerWithArgs(int $n)
    {
        Assert::eq(5, $n);
    }
}
