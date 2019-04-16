<?php

namespace Datashaman\PHPCheck\Tests;

use Datashaman\PHPCheck\Gen;

class GenTestCase extends \PHPUnit\Framework\TestCase
{
    protected function _testGenerator(
        Gen $gen,
        callable $callable
    ) {
        $counter = 0;

        while ($counter < 100) {
            $value = $gen->generate();
            call_user_func($callable, $value);
            $counter++;
        }
    }

}
