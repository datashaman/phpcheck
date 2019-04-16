<?php

namespace Datashaman\PHPCheck\Tests;

use Datashaman\PHPCheck\MkGen;
use Datashaman\PHPCheck\Gen;
use Datashaman\PHPCheck\Types\Maybe;

class MkGenTest extends GenTestCase
{
    public function testChooseWithFloatRange()
    {
        $this->_testGenerator(
            gen()->choose([0.0, 10.0]),
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
            gen()->choose([0, 10]),
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
            gen()->choose(['a', 'z']),
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
            gen()->choose(0.0, 10.0),
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
            gen()->choose(0, 10),
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
            gen()->choose('a', 'z'),
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
            gen()->listOf(gen()->choose('a', 'z')),
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
            gen()->oneof(
                gen()->choose('a', 'z'),
                gen()->choose(0, 10)
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
            gen()->resize(3, gen()->listOf(gen()->choose('a', 'z'))),
            function ($current) {
                $this->assertIsArray($current);
                $this->assertLessThanOrEqual(3, count($current));
            }
        );
    }

    public function testScaleListOfCharacters()
    {
        $this->_testGenerator(
            gen()->scale(
                function (int $size) {
                    return $size / 3;
                },
                gen()->listOf(gen()->choose('a', 'z'))
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
            gen()->suchThat(
                gen()->choose(0, 10),
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
            gen()->suchThatMap(
                gen()->choose(0, 10),
                function (int $value) {
                    $value = $value + 10;

                    return Maybe::unit($value);
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
            gen()->suchThatMaybe(
                gen()->choose(0, 10),
                function (int $value) {
                    return $value > 5;
                }
            ),
            function (Maybe $current) {
                $this->assertGreaterThan(5, $current->value());
            }
        );
    }

    public function testSuchThatNothing()
    {
        $this->_testGenerator(
            gen()->suchThatMaybe(
                gen()->choose(0, 10),
                function (int $value) {
                    return $value > 10;
                }
            ),
            function (Maybe $current) {
                $this->assertTrue($current->isNothing());
        }
            );
    }

    public function testVariant()
    {
        $this->_testGenerator(
            gen()->variant(
                123,
                gen()->choose(0, 1000000)
            ),
            function ($current) {
                $this->assertEquals(309391, $current);
            }
        );
    }
}
