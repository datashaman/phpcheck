<?php

namespace Datashaman\PHPCheck\Tests;

class GenTest extends GenTestCase
{
    public function testMap()
    {
        $this->_testGenerator(
            gen()
                ->choose(0, 500)
                ->map(
                    function (int $i) {
                        return $i / 2;
                    }
                ),
            function (int $current) {
                $this->assertLessThanOrEqual(250, $current);
            }
        );
    }

    public function testFilter()
    {
        $this->_testGenerator(
            gen()
                ->choose(0, 500)
                ->filter(
                    function (int $i) {
                        return $i > 5;
                    }
                ),
            function (int $current) {
                $this->assertGreaterThan(5, $current);
            }
        );
    }

    public function testFlatmap()
    {
        $this->_testGenerator(
            gen()
                ->choose(5, 5)
                ->flatmap(
                    function (int $i) {
                        return gen()->vectorOf($i, gen()->chooseAny('int'));
                    }
                ),
            function (array $array) {
                $this->assertCount(5, $array);
                foreach ($array as $value) {
                    $this->assertIsInt($value);
                }
            }
        );
    }
}
