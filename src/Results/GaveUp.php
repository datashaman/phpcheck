<?php

namespace Datashaman\PHPCheck\Results;

class GaveUp
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
     * The number of test cases having each combination of labels
     *
     * @var array
     */
    public $labels;

    /**
     * The number of test cases having each class
     *
     * @var array
     */
    public $classes;

    /**
     * Data collected by tabulate
     *
     * @var array
     */
    public $tables;

    /**
     * Printed output
     *
     * @var string
     */
    public $output;
}
