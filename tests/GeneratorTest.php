<?php

namespace Datashaman\PHPCheck\Tests;

use function Datashaman\PHPCheck\{
    choose,
    generate,
    listOf,
    oneof,
    resize,
    scale,
    suchThat,
    suchThatMap,
    suchThatMaybe,
    variant
};

use Datashaman\Logic\Maybe;
use function Datashaman\Logic\isNothing;
use function Datashaman\Logic\mkMaybe;
use Generator;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{
    protected function _testGenerator(
        Generator $gen,
        callable $callable
    ) {
        $counter = 0;

        while ($counter < 100) {
            $value = generate($gen);
            call_user_func($callable, $value);
            $counter++;
        }
    }

    public function testChooseWithFloatRange()
    {
        $this->_testGenerator(
            choose([0.0, 10.0]),
            function ($current) {
                $this->assertIsFloat($current);
                $this->assertGreaterThanOrEqual(0.0, $current);
                $this->assertLessThanOrEqual(10.0, $current);
            }
        );
    }

    public function testChooseWithIntRange()
    {
        $this->_testGenerator(
            choose([0, 10]),
            function ($current) {
                $this->assertIsInt($current);
                $this->assertGreaterThanOrEqual(0, $current);
                $this->assertLessThanOrEqual(10, $current);
            }
        );
    }

    public function testChooseWithCharacterRange()
    {
        $this->_testGenerator(
            choose(['a', 'z']),
            function ($current) {
                $this->assertIsString($current);
                $this->assertGreaterThanOrEqual('a', $current);
                $this->assertLessThanOrEqual('z', $current);
            }
        );
    }

    public function testChooseWithFloatArgs()
    {
        $this->_testGenerator(
            choose(0.0, 10.0),
            function ($current) {
                $this->assertIsFloat($current);
                $this->assertGreaterThanOrEqual(0.0, $current);
                $this->assertLessThanOrEqual(10.0, $current);
            }
        );
    }

    public function testChooseWithIntArgs()
    {
        $this->_testGenerator(
            choose(0, 10),
            function ($current) {
                $this->assertIsInt($current);
                $this->assertGreaterThanOrEqual(0, $current);
                $this->assertLessThanOrEqual(10, $current);
            }
        );
    }

    public function testChooseWithCharacterArgs()
    {
        $this->_testGenerator(
            choose('a', 'z'),
            function ($current) {
                $this->assertIsString($current);
                $this->assertGreaterThanOrEqual('a', $current);
                $this->assertLessThanOrEqual('z', $current);
            }
        );
    }

    public function testListOfCharacters()
    {
        $this->_testGenerator(
            listOf(choose('a', 'z')),
            function ($current) {
                $this->assertIsArray($current);
                foreach ($current as $char) {
                    $this->assertGreaterThanOrEqual('a', $char);
                    $this->assertLessThanOrEqual('z', $char);
                }
            }
        );
    }

    public function testOneof()
    {
        $this->_testGenerator(
            oneof(
                [
                    choose('a', 'z'),
                    choose(0, 10),
                ]
            ),
            function ($current) {
                $this->assertTrue(
                    $current >= 'a' && $current <= 'z'
                    || $current >= 0 && $current <= 10
                );
            }
        );
    }

    public function testResizedListOfCharacters()
    {
        $this->_testGenerator(
            resize(3, listOf(choose('a', 'z'))),
            function ($current) {
                $this->assertIsArray($current);
                $this->assertLessThanOrEqual(3, count($current));
            }
        );
    }

    public function testScaleListOfCharacters()
    {
        $this->_testGenerator(
            scale(
                function (int $size) {
                    return $size / 3;
                },
                listOf(choose('a', 'z'))
            ),
            function ($current) {
                $this->assertIsArray($current);
                $this->assertLessThanOrEqual(10, count($current));
            }
        );
    }

    public function testSuchThat()
    {
        $this->_testGenerator(
            suchThat(
                choose(0, 10),
                function (int $value) {
                    return $value > 5;
                }
            ),
            function (int $current) {
                $this->assertGreaterThan(5, $current);
            }
        );
    }

    public function testSuchThatMap()
    {
        $this->_testGenerator(
            suchThatMap(
                choose(0, 10),
                function (int $value) {
                    $value = $value + 10;

                    return mkMaybe($value);
                }
            ),
            function (int $current) {
                $this->assertGreaterThanOrEqual(10, $current);
                $this->assertLessThanOrEqual(20, $current);
            }
        );
    }

    public function testSuchThatMaybe()
    {
        $this->_testGenerator(
            suchThatMaybe(
                choose(0, 10),
                function (int $value) {
                    return $value > 5;
                }
            ),
            function (Maybe $current) {
                $this->assertGreaterThan(5, $current());
            }
        );
    }

    public function testSuchThatNothing()
    {
        $this->_testGenerator(
            suchThatMaybe(
                choose(0, 10),
                function (int $value) {
                    return $value > 10;
                }
            ),
            function (Maybe $current) {
                $this->assertTrue(isNothing($current));
            }
        );
    }

    public function testVariant()
    {
        $this->_testGenerator(
            variant(
                123,
                choose(0, 1000000)
            ),
            function ($current) {
                $this->assertEquals(857674, $current);
            }
        );
    }
}
