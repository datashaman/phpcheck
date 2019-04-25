<?php

namespace Datashaman\PHPCheck\Checks;

use Exception;
use Webmozart\Assert\Assert;

function add(string $numbers)
{
    $header = strtok($numbers, "\n");
    $numbers = strtok("\n");

    if(preg_match('#^//(.*)#', $header, $match)) {
        $delimiter = $match[1];
    } else {
        throw new Exception('Garbage header');
    }

    $ints = array_map('intval', explode($delimiter, $numbers));

    if (
        array_filter(
            $ints,
            function ($int) {
                return $int < 0;
            }
        )
    ) {
        throw new Exception('Negatives not allowed');
    }

    return array_sum($ints);
}

class KataCheck
{
    /**
     * @param string $delimiter {@gen choose("!", "/")}
     * @param array $ints {@gen resize(3, listOf(choose(0, 500)))}
     */
    public function checkBasicInput(string $delimiter, array $ints)
    {
        $numbers = "//$delimiter\n" . implode($delimiter, $ints);

        return array_sum($ints) === add($numbers);
    }

    /**
     * @param string $delimiter {@gen choose("!", ",")}
     * @param array $ints {@gen vectorOf(4, choose(-100, -1))}
     */
    public function checkNegatives(string $delimiter, array $ints)
    {
        $numbers = "//$delimiter\n" . implode($delimiter, $ints);

        try {
            add($numbers);
        }

        catch (Exception $e) {
            return true;
        }

        return false;
    }
}
