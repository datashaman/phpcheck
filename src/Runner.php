<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

use DateTime;
use Exception;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use SimpleXMLElement;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Throwable;
use Webmozart\Assert\Assert;

class Runner implements EventSubscriberInterface
{
    public const MAX_DISCARD_RATIO = 10;

    public const MAX_SIZE = 100;

    public const MAX_SUCCESS = 100;

    protected const CONFIG_FILE = 'phpcheck.xml';

    private $seed;

    private $maxSuccess;

    private $output;

    private $totalIterations = 0;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END_ALL   => 'onEndAll',
            CheckEvents::FAILURE   => 'onFailure',
            CheckEvents::END       => 'onEnd',
            CheckEvents::ERROR     => 'onError',
            CheckEvents::START     => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
            CheckEvents::SUCCESS   => 'onSuccess',
        ];
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getTotalIterations()
    {
        return $this->totalIterations;
    }

    public function iterate(
        callable $subject,
        callable $check = null,
        array $options = []
    ): array {
        $maxSuccess = $options['maxSuccess'] ?? $this->maxSuccess;

        $result = $this->checks(
            $maxSuccess,
            $subject,
            $check,
            $options
        );

        $this->totalIterations += $result['iteration'];

        return $result;
    }

    protected function gatherMethods($args)
    {
        $methods = [];
        $paths = $this->gatherPaths($args->output, $args->path);

        foreach ($paths as $path) {
            $classes = \get_declared_classes();

            include $path;

            $classes = \array_diff(
                \get_declared_classes(),
                $classes
            );

            $classes = \array_filter(
                $classes,
                function ($class) {
                    return \preg_match('/Check$/', $class) === 1;
                }
            );

            $testClass = \array_pop($classes);

            [$classFilter, $methodFilter] = $this->getFilter($args);

            if ($classFilter && !\fnmatch($classFilter, $testClass)) {
                continue;
            }

            $class = app('reflection')->getClass($testClass);
            $methods[$testClass] = [];

            foreach ($class->getMethods() as $method) {
                $name = $method->getName();

                if ($methodFilter && !\fnmatch($methodFilter, $name)) {
                    continue;
                }

                if (\preg_match('/^check/', $method->getName()) !== 1) {
                    continue;
                }

                $methods[$testClass][] = $method;
            }
        }

        return $methods;
    }

    public function execute(Args $args): void
    {
        $state = app('state');

        $output = $this->output = $args->output;

        if ($args->coverageHtml !== false) {
            if (null === $args->coverageHtml) {
                $output->writeln('<error>You must specify a directory for coverage-html</error>');
                exit(1);
            }
            $coverage = new Coverage\HtmlCoverage($args->coverageHtml);
        }

        if ($args->coverageText !== false) {
            $coverage = new Coverage\TextCoverage(
                $args->coverageText,
                $args->noAnsi
            );
        }

        $config = $this->getConfig();

        $this->maxSuccess = (int) $args->maxSuccess;

        $dispatcher = app('dispatcher');

        if ($args->logJunit !== false) {
            if (null === $args->logJunit) {
                $output->writeln('<error>You must specify a filename for log-junit</error>');
                exit(1);
            }
            $reporter = new Subscribers\JUnitReporter();
            $dispatcher->addSubscriber($reporter);
        }

        if ($args->logText !== false) {
            if (null === $args->logText) {
                $output->writeln('<error>You must specify a filename for log-text</error>');
                exit(1);
            }
            $reporter = new Subscribers\TextReporter();
            $dispatcher->addSubscriber($reporter);
        }

        $bootstrap = $args->bootstrap
            ?: $config['bootstrap']
            ?? null;

        if ($bootstrap) {
            include_once $bootstrap;
        }

        if (isset($config->subscribers)) {
            foreach ($config->subscribers->subscriber as $subscriber) {
                $class = (string) $subscriber['class'];
                $dispatcher->addSubscriber(new $class($this));
            }
        }

        $event = new Events\StartAllEvent();
        $dispatcher->dispatch(CheckEvents::START_ALL, $event);

        $allFunctions = [];

        if ($args->path) {
            $allFunctions = $this->gatherMethods($args);
        } elseif ($args->subject) {
            $allFunctions = [null => [new ReflectionFunction($args->subject)]];
        }

        foreach ($allFunctions as $testClass => $functions) {
            $test = null;

            if ($testClass) {
                $test = new $testClass();
            }

            foreach ($functions as $function) {
                $tags           = $this->getTags($function);
                $event = new Events\StartEvent($function, $tags);
                $dispatcher->dispatch(CheckEvents::START, $event);

                $parameterCount = \count($function->getParameters());

                try {
                    if (!$parameterCount) {
                        $test ? $function->invoke($test) : $function->invoke();
                    } else {
                        $noDefects  = ($args->noDefects !== false);
                        $defectArgs = $state->getDefectArgs($function);

                        if (!$noDefects && $defectArgs) {
                            try {
                                $test
                                    ? $function->invoke($test, ...$defectArgs)
                                    : $function->invoke(...$defectArgs);
                            } catch (Throwable $throwable) {
                                throw new Exceptions\ExecutionError($defectArgs, $throwable);
                            }
                        }

                        $result = $this->iterate(
                            $test ? $function->getClosure($test) : $function->getClosure(),
                            null,
                            $tags
                        );

                        if (!$result['passed']) {
                            throw new Exceptions\ExecutionFailure($result['input']);
                        }
                    }

                    $event = new Events\SuccessEvent($function, $tags);
                    $dispatcher->dispatch(CheckEvents::SUCCESS, $event);
                    $status = 'SUCCESS';
                } catch (Exceptions\ExecutionFailure $failure) {
                    $event = new Events\FailureEvent(
                        $function,
                        $tags,
                        $failure->getArgs()
                    );
                    $dispatcher->dispatch(CheckEvents::FAILURE, $event);
                    $status = 'FAILURE';
                } catch (Exceptions\ExecutionError $error) {
                    $event = new Events\ErrorEvent(
                        $function,
                        $tags,
                        $error->getArgs(),
                        $error->getCause()
                    );
                    $dispatcher->dispatch(CheckEvents::ERROR, $event);
                    $status = 'ERROR';
                }

                $event = new Events\EndEvent($function, $tags, $status);
                $dispatcher->dispatch(CheckEvents::END, $event);
            }
        }

        $event = new Events\EndAllEvent();
        $dispatcher->dispatch(CheckEvents::END_ALL, $event);
    }

    private function checks(
        int $size,
        callable $subject,
        callable $check = null,
        array $tags = null
    ): array {
        $reflection = app('reflection')->reflect($subject);
        $signature  = app('reflection')->getFunctionSignature($reflection);

        $check = null === $check
            ? $reflection
            : app('reflection')->reflect($check);

        $dispatcher = app('dispatcher');
        $result     = [];

        for ($iteration = 1; $iteration <= $size; $iteration++) {
            $input = generate(resize(
                \min($iteration - 1, self::MAX_SIZE),
                arguments($subject)
            ));
            [$passed, $output, $error] = $this->passed($reflection, $check, $input);

            $event = new Events\IterationEvent($reflection, $tags, $input, $passed);
            $dispatcher->dispatch(CheckEvents::ITERATION, $event);

            $result = \compact('signature', 'input', 'output', 'passed', 'iteration', 'error');

            $exhausted = false;

            while (!$passed) {
                try {
                    $input = $this->shrink($input);
                } catch (Exceptions\Example $e) {
                    $input     = $e->args;
                    $exhausted = true;
                }

                [$passed, $output, $error] = $this->passed($reflection, $check, $input);

                if ($passed) {
                    break;
                }

                $result = \compact('signature', 'input', 'output', 'passed', 'iteration', 'error');

                if ($exhausted) {
                    break;
                }
            }

            if (!$result['passed']) {
                break;
            }
        }

        return $result;
    }

    private function getConfig(string $filename = null): ?SimpleXMLElement
    {
        $filename = self::CONFIG_FILE;

        $filenames = [
            $filename,
            "$filename.dist",
        ];

        foreach ($filenames as $filename) {
            if (\file_exists($filename)) {
                return \simplexml_load_file($filename) ?: null;
            }
        }

        return null;
    }

    private function gatherPaths(OutputInterface $output, string $pathArgument): array
    {
        $path         = \realpath($pathArgument);

        if ($pathArgument && !$path) {
            $output->writeln('Path does not exist');
            exit(1);
        }

        if ($path && !\file_exists($path)) {
            $output->writeln('Path does not exist');
            exit(1);
        }

        if (\is_file($path)) {
            return [$path];
        }

        $paths = [];

        $finder = new Finder();
        $finder->files()->in($path)->name('*Check.php');

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $paths[] = $file->getRealPath();
            }
        }

        return $paths;
    }

    private function getTags(ReflectionFunctionAbstract $function)
    {
        $factory    = DocBlockFactory::createInstance();
        $docComment = $function->getDocComment();

        if ($docComment === false) {
            return [];
        }

        $docBlock = $factory->create($docComment);

        $tags = $docBlock->getTags();

        $result = [
            'coverTable' => [],
            'tabulate'   => [],
        ];

        foreach ($tags as $tag) {
            $tagName     = $tag->getName();
            $description = (string) $tag->getDescription();

            if ($tagName === 'coverTable') {
                if (\preg_match('/^"([^"]*)"\s+(.*)$/', $description, $match)) {
                    $label                        = $match[1];
                    $expression                   = $match[2];
                    $result['coverTable'][$label] = $expression;
                } else {
                    throw new Exception('Unable to parse coverTable tag: ' . $description);
                }
                $result['coverTable'][] = $description;
            } elseif ($tagName === 'maxSuccess') {
                $result['maxSuccess'] = (int) $description;
            } elseif ($tagName === 'tabulate') {
                if (\preg_match('/^"([^"]*)"\s+(.*)$/', $description, $match)) {
                    $label                      = $match[1];
                    $expression                 = $match[2];
                    $result['tabulate'][$label] = $expression;
                }
            } elseif ($tagName === 'within') {
                $result['within'] = (int) $description;
            }
        }

        return $result;
    }

    private function getFilter(Args $args)
    {
        $filter = $args->filter;

        if (!$filter) {
            return [null, null];
        }

        $parts = \explode('::', $filter);

        Assert::countBetween($parts, 1, 2);

        if (\count($parts) === 1) {
            return [null, $parts[0]];
        }

        return $parts;
    }

    private function shrink($args)
    {
        foreach ($args as &$arg) {
            if (\is_string($arg)) {
                if (\mb_strlen($arg)) {
                    $shrunkArg = \mb_substr($arg, 0, -1);
                    $arg       = $shrunkArg;

                    return $args;
                }
            } elseif (\is_array($arg)) {
                if (\count($arg)) {
                    $shrunkArg = \array_slice($arg, 0, \count($arg) - 1);
                    $arg       = $shrunkArg;

                    return $args;
                }
            } elseif (\is_bool($arg)) {
                if ($arg === true) {
                    $arg = false;

                    return $args;
                }
            } elseif (\is_int($arg)) {
                if ($arg !== 0) {
                    $arg = (int) ($arg / 2);

                    return $args;
                }
            } elseif ($arg instanceof DateTime) {
                $timestamp  = $arg->getTimestamp();
                $difference = $timestamp - (new DateTime('2000-01-01'))->getTimestamp();

                if ($difference) {
                    $timestamp = (int) ($timestamp - $difference / 2);

                    $arg = new DateTime("@$timestamp");

                    return $args;
                }
            } else {
                throw new Exception('Add another shrink handler');
            }
        }

        throw new Exceptions\Example($args);
    }

    private function passed(
        ReflectionFunctionAbstract $subject,
        ReflectionFunctionAbstract $check,
        array $input
    ) {
        $error  = null;
        $output = null;
        $passed = null;

        try {
            \set_error_handler(
                function ($code, $message, $file, $line): void {
                    throw new Exceptions\CheckError(
                        $message,
                        $code,
                        $file,
                        $line
                    );
                }
            );

            if ($subject == $check) {
                $passed = $subject->invoke(...$input);
            } else {
                $output = $subject->invoke(...$input);
                $passed = $check->invoke($input, $output);
            }
        } catch (Exceptions\CheckError $error) {
            $passed = false;
        }

        return [$passed, $output, $error];
    }
}
