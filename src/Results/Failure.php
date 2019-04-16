<?php

namespace Datashaman\PHPCheck\Results;

class Failure
{
    /**
     * Number of tests performed
     *
     * @var int
     */
    public $numTests;

    /**
     * Number of tests skipped
     *
     * @var int
     */
    public $numDiscarded;

    /**
     * Number of successful shrinking steps performed
     *
     * @var int
     */
    public $numShrinks;

    /**
     * Number of unsuccessful shrinking steps performed
     *
     * @var int
     */
    public $numShrinkTries;

    /**
     * Number of unsuccessful shrinking steps performed since last successful shrink
     *
     * @var int
     */
    public $numShrinkFinal;

    /**
     * What seed was used
     *
     * @var int
     */
    public $usedSeed;

    /**
     * What was the test size
     *
     * @var int
     */
    public $usedSize;

    /**
     * Why did the property fail
     *
     * @var string
     */
    public $reason;

    /**
     * The exception the property threw, if any
     *
     * @var Exception|null
     */
    public $theException;

    /**
     * Printed output
     *
     * @var string
     */
    public $output;

    /**
     * The test case which provoked the failure
     *
     * @var array
     */
    public $failingTestCase;

    /**
     * The test cases's labels
     *
     * @var array
     */
    public $failingLabels;

    /**
     * The test case's classes
     *
     * @var array
     */
    public $failingClasses;
}
