# phpcheck

![Development Status](https://img.shields.io/badge/status-alpha-red.svg)
[![Build Status](https://travis-ci.org/datashaman/phpcheck.svg?branch=master)](https://travis-ci.org/datashaman/phpcheck)

PHP implementation of Haskell's QuickCheck.

*NB* This is *ALPHA* status. Do not use it yet.

Table of Contents
=================

   * [phpcheck](#phpcheck)
      * [type declarations](#type-declarations)
      * [annotations](#annotations)
      * [generators](#generators)
      * [examples](#examples)
      * [command line arguments](#command-line-arguments)
      * [storage of results](#storage-of-results)

## type declarations

`PHPCheck` will automatically generate arguments for check methods based on type declarations. For finer-grained control
over the arguments, use annotations on the method parameters.

## annotations

Annotate your check method parameters to control the arguments provided to the method.

Parameter tags (use them in the description of a parameter, usually the end):

* `{@gen name}` or `{@gen name:params}` where `name` is the name of the generator and `params` is a JSON encoded array of arguments passed to the generator.

Method tags:

* `@iterates` indicates that this check method handles its own iteration, and should be called once with no parameters.
* `@iterations` sets the number of iterations for this check method. The default is 100.

## generators

Below is the list of generators that are currently available:

* `ascii(Generator $sizes = null)`
* `booleans(int $chanceOfGettingTrue = 50)`
* `characters($minChar, $maxChar)`
* `choose(array $arr)`
* `floats(float $min, float $max, Generator $decimals = null)`
* `integers(int $min = PHP_INT_MIN, int $max = PHP_INT_MAX)`
* `intervals(array $include = [[PHP_INT_MIN, PHP_INT_MAX]], array $exclude=[])`
* `listOf(Generator $values = null, Generator $sizes = null)`
* `strings(Generator $sizes = null, Generator $characters = null)`

You have to nest the parameter tag to specify a generator argument. See [GenCheck.php](checks/GenCheck.php) for examples.

The `characters` generator accepts either characters or integer codepoints for `minChar` and `maxChar`. Characters
are generated from the complete range of Unicode characters excluding control characters, private ranges and surrogates.

## examples

There is an example check implemented in the _examples_ folder. To run it:

    phpcheck examples

The [_Gen_ class checks](checks/GenCheck.php) for this package are a great illustration of the use of the generators.

## command line arguments

The `phpcheck` program accept a number of arguments and options:

    Description:
        Runs checks.

    Usage:
        phpcheck [options] [--] [<path>]

    Arguments:
        path                           File or folder with checks [default: "checks"]

    Options:
        --bootstrap[=BOOTSTRAP]        A PHP script that is included before the checks run
        -f, --filter[=FILTER]          Filter the checks that will be run
        -i, --iterations=ITERATIONS    How many times each check will be run [default: 100]
        -j, --log-junit[=LOG-JUNIT]    Log check execution in JUnit XML format to file
        -d, --no-defects[=NO-DEFECTS]  Ignore previous defects [default: false]
        -h, --help                     Display this help message
        -q, --quiet                    Do not output any message
        -V, --version                  Display this application version
        --ansi                         Force ANSI output
        --no-ansi                      Disable ANSI output
        -n, --no-interaction           Do not ask any interactive question
        -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

The `--bootstrap` parameter can be included in a _phpcheck.xml_ or _phpcheck.xml.dist_ file. See [ours](phpcheck.xml.dist) for an example.

The `--filter` or `-f` parameter is a filename-style match as follows:

    ClassName::
    ClassName::MethodName
    MethodName

where `ClassName` and `MethodName` can include patterns using `*` and `?` as you'd expect.

The console reporter outputs check results much like `PHPUnit`:

    PHPCheck 0.1.0 by Marlin Forbes and contributors.

    .............

    13 / 13 (100%)

    Time: 284 ms, Memory: 6.00 MB

    OK (Checks: 13, Iterations: 120006, Failures: 0, Errors: 0)

Using `---verbose 3` or `-vvv` enables a list of the checks as they are run:

    PHPCheck 0.1.0 by Marlin Forbes and contributors.

    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkCharacters' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkCharacters' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkStrings' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkStrings' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkAscii' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkAscii' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkBooleans' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkBooleans' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkBooleansWithPercentage' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkBooleansWithPercentage' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkCharactersWithNumbers' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkCharactersWithNumbers' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkCharactersWithStrings' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkCharactersWithStrings' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkChoose' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkChoose' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkIterations' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkIterations' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkFloats' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkFloats' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkFloatsWithDecimalGen' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkFloatsWithDecimalGen' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkStringsWithMinMax' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkStringsWithMinMax' ended
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkListOfInts' started
    Check 'Datashaman\PHPCheck\Checks\GenCheck::checkListOfInts' ended

    Time: 305 ms, Memory: 6.00 MB

    OK (Checks: 13, Iterations: 120006, Failures: 0, Errors: 0)

## storage of results

`PHPCheck` stores results of check execution in the `.phpcheck` folder of the project. You should add that to your `.gitignore` file.

When `PHPCheck` finds an error or failure, it will retry the defective arguments first before going onto regular iterations with new arguments.

If you wish to ignore the previous defects and run through new iterations only, use `--no-defects` or `-d`.
