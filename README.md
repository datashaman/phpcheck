# phpcheck

![Development Status](https://img.shields.io/badge/status-alpha-red.svg)
[![Build Status](https://travis-ci.org/datashaman/phpcheck.svg?branch=master)](https://travis-ci.org/datashaman/phpcheck)

PHP implementation of Haskell's QuickCheck.

*NB* This is *ALPHA* status. Do not use it yet.

Table of Contents
=================

   * [phpcheck](#phpcheck)
      * [installation](#installation)
      * [type declarations](#type-declarations)
      * [annotations](#annotations)
        * [tabulate](#tabulate)
      * [generators](#generators)
      * [assertions](#assertions)
      * [examples](#examples)
      * [command line arguments](#command-line-arguments)
      * [storage of results](#storage-of-results)
      * [todo](#todo)

## installation

Install the composer package into your project. You will require `PHP7.2` or higher:

    composer require --dev datashaman/phpcheck

Alternately, you can install the composer package globally:

    composer global require datashaman/phpcheck

## type declarations

`PHPCheck` will automatically generate arguments for check methods based on type declarations.

For finer-grained control over the arguments, use annotations on the method parameters.

## annotations

Annotate your check method parameters to control the arguments provided to the method.

Parameter tags (use them in the description of a parameter, usually the end):

* `{@gen method()}` or `{@gen method(1, 10)}` where `method` is the name of a generator below.

Method tags:

* `@maxSuccess` sets the number of successful checks for a successful result. The default is 100.
* `@tabulate` and `@coverTable` (dicussed below).

### tabulate

If you decorate a check method with `tabulate`, information about test case distribution is collected into a table.

The arguments to `tabulate` are the table's name and a _list_ of values associated with the current check. An example:

    /**
     * @tabulate "Values" [$value]
     */
    public function checkBooleans(bool $value)
    {
        return true;
    }

If you run this check, everything passes and a table is output at the end of the check run:

    Tables

    1) Datashaman\PHPCheck\Checks\GeneratorCheck::checkBooleans

    Values (100 total)

       52% true
       48% false

We'd like to check that the coverage is correct for the generator, so we use a `@coverTable` method annotation:

    /**
     * @coverTable "Values" [[true, 49], [false, 49]]
     * @tabulate "Values" [$value]
     */
    public function checkBooleans(bool $value)
    {
        return true;
    }

The arguments to the annotation are the name of the table, and a list of key value pairs where the value is the expected percentage distribution.

Here's a sample output from the above:

    1) Datashaman\PHPCheck\Checks\GeneratorCheck::checkBooleans

    Values (100 total)

    54% true
    46% false

    Table 'Values' had only 46.0% false, but expected 49.0%

We are now warned when the distribution does not fall within the accepted percentage of generated values.

In this case, we would benefit from running the checks a lot more times so we approach the expected _50/50_ average for a boolean:

    /**
     * @coverTable "Values" [[true, 49], [false, 49]]
     * @maxSuccess 10000
     * @tabulate "Values" [$value]
     */
    public function checkBooleans(bool $value)
    {
        return true;
    }

Now with _10000_ successful iterations, the warning disappears from the output and the percentage is within the acceptable _1%_ margin of error:

    1) Datashaman\PHPCheck\Checks\GeneratorCheck::checkBooleans

    Values (10000 total)

        50.5% true
        49.5% false

## generators

Below is the list of generators that are currently available:

* `arguments(callable $callable)`
* `arrays()`
* `ascii()`
* `booleans(int $chanceOfGettingTrue = 50)`
* `characters($minChar = null, $maxChar = null)`
* `choose($min = PHP_INT_MIN, $max = PHP_INT_MAX)`
* `chooseAny(string $type)`
* `datetimes($min = null, $max = null, Generator $timezones = null)`
* `dates($min = null, $max = null)`
* `elements(array $array)`
* `faker(...$args)`
* `floats(float $min, float $max)`
* `frequency(array $frequencies)`
* `growingElements(array $array)`
* `integers(int $min = PHP_INT_MIN, int $max = PHP_INT_MAX)`
* `intervals(array $include = [[PHP_INT_MIN, PHP_INT_MAX]], array $exclude=[])`
* `listOf(Generator $gen)`
* `listOf1(Generator $gen)`
* `mixed()`
* `oneof(array $gens)`
* `resize(int $n, Generator $gen)`
* `scale(callable $f, Generator $gen)`
* `strings(Generator $characters = null)`
* `suchThat(Generator $gen, callable $f)`
* `suchThatMap(Generator $gen, callable $f)`
* `suchThatMaybe(Generator $gen, callable $f)`
* `timezones()`
* `variant(int $seed, Generator $gen)`
* `vectorOf(int $n, Generator $gen)`

To generate a value from one of the above, use `generate`:

    >>> use function Datashaman\PHPCheck\{
    ...     choose,
    ...     generate
    ... };
    >>>
    >>> var_dump(generate(choose(0, 10)));
    int(2)

To generate a sample of values with increasing size, use `sample`:

    >>> use function Datashaman\PHPCheck\{
    ...     ascii,
    ...     strings,
    ...     sample
    ... };
    >>>
    >>> var_dump(sample(strings(ascii())));
    array(11) {
    [0]=>
    string(0) ""
    [1]=>
    string(2) "0]"
    [2]=>
    string(2) "^|"
    [3]=>
    string(3) "P@N"
    [4]=>
    string(5) "G1KPu"
    [5]=>
    string(5) "q-e1y"
    [6]=>
    string(4) "NcdL"
    [7]=>
    string(7) "hS:{_>@"
    [8]=>
    string(10) "wjv1X"Zm$V"
    [9]=>
    string(16) "aX-6*s0-WX>#cf~T"
    [10]=>
    string(12) ";g<&8*b&Q0=)"
    }
    => null

See [GeneratorCheck](checks/GeneratorCheck.php) and [GeneratorTest](tests/GeneratorTest.php) for examples of how these are used.

The `characters` generator accepts either characters or integer codepoints for `minChar` and `maxChar`. Characters
are generated from the complete range of Unicode characters excluding control characters, private ranges and surrogates.

The `faker` generator takes a variable number of arguments. If you supply one argument, it's assumed to be a property on the `Faker`
generator. If you supply more than one argument, the first argument is the method on the `Faker` generator and the rest are sent as parameters to that method.

This opens up a lot of domain-specific generators. See [Faker](https://github.com/fzaninotto/Faker) for more details.

## check methods

Check methods must return a bool indicating success or failure.

## exceptions

We have chosen to use [Nuno Maduro's Collision](https://github.com/nunomaduro/collision) for reporting exceptions to the console.

This should be switchable soon.

## examples

There is an example check implemented in the _examples_ folder. To run it:

    phpcheck examples

The [_Generator_ checks](checks/GeneratorCheck.php) for this package are a great illustration of the use of the generators.

## command line arguments

The `phpcheck` program accept a number of arguments and options:

    Description:
        Runs checks.

    Usage:
        phpcheck [options] [--] [<path>]

    Arguments:
        path                           File or folder with checks [default: "checks"]

    Options:
            --bootstrap[=BOOTSTRAP]         A PHP script that is included before the checks run
            --coverage-html[=COVERAGE-HTML] Generate HTML code coverage report [default: false]
            --coverage-text[=COVERAGE-TEXT] Generate text code coverage report [default: false]
        -f, --filter[=FILTER]               Filter the checks that will be run
        -j, --log-junit[=LOG-JUNIT]         Log check execution to JUnit XML file [default: false]
        -t, --log-text[=LOG-TEXT]           Log check execution to text file [default: false]
        --max-success=MAX-SUCCESS           Maximum number of successful checks before succeeding. Testing stops at the first failure.
                                            If all tests are passing and you want to run more tests, increase this number. [default: 100]
        -d, --no-defects[=NO-DEFECTS]       Ignore previous defects [default: false]
        -h, --help                          Display this help message
        -q, --quiet                         Do not output any message
        -s, --seed[=SEED]                   Seed the random number generator to get repeatable runs
        -V, --version                       Display this application version
        --ansi                              Force ANSI output
        --no-ansi                           Disable ANSI output
        -n, --no-interaction                Do not ask any interactive question
        -v|vv|vvv, --verbose                Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

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

    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkCharacters' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkCharacters' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkStrings' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkStrings' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkAscii' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkAscii' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkBooleans' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkBooleans' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkBooleansWithPercentage' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkBooleansWithPercentage' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkCharactersWithNumbers' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkCharactersWithNumbers' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkCharactersWithStrings' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkCharactersWithStrings' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkChoose' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkChoose' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkIterations' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkIterations' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkFloats' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkFloats' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkFloatsWithDecimalGen' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkFloatsWithDecimalGen' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkStringsWithMinMax' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkStringsWithMinMax' ended
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkListOfInts' started
    Check 'Datashaman\PHPCheck\Checks\GeneratorCheck::checkListOfInts' ended

    Time: 305 ms, Memory: 6.00 MB

    OK (Checks: 13, Iterations: 120006, Failures: 0, Errors: 0)

## storage of results

`PHPCheck` stores results of check execution in the `.phpcheck` folder of the project.

You should add the folder to your `.gitignore` file.

When `PHPCheck` finds an error or failure, it will retry the defective arguments first before going onto regular iterations with new arguments.

If you wish to ignore the previous defects and run through new iterations only, use `--no-defects` or `-d`.

## todo

All todo items have been captured as [issues](https://github.com/datashaman/phpcheck/issues).
