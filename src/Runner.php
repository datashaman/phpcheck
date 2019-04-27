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

use Exception;
use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use SimpleXMLElement;
use Symfony\Component\Console\Input\InputInterface;
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

    private $input;

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

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $state = app('state');

        $this->input  = $input;
        $this->output = $output;

        if ($input->getOption('coverage-html') !== false) {
            if (null === $input->getOption('coverage-html')) {
                $output->writeln('<error>You must specify a directory for coverage-html</error>');
                exit(1);
            }
            $coverage = new Coverage\HtmlCoverage($this);
        }

        if ($input->getOption('coverage-text') !== false) {
            $coverage = new Coverage\TextCoverage($this);
        }

        $config = $this->getConfig();

        $this->maxSuccess = (int) $input->getOption('max-success');

        $dispatcher = app('dispatcher');

        if ($input->getOption('log-junit') !== false) {
            if (null === $input->getOption('log-junit')) {
                $output->writeln('<error>You must specify a filename for log-junit</error>');
                exit(1);
            }
            $reporter = new Subscribers\JUnitReporter($this);
            $dispatcher->addSubscriber($reporter);
        }

        if ($input->getOption('log-text') !== false) {
            if (null === $input->getOption('log-text')) {
                $output->writeln('<error>You must specify a filename for log-text</error>');
                exit(1);
            }
            $reporter = new Subscribers\TextReporter($this);
            $dispatcher->addSubscriber($reporter);
        }

        $bootstrap = $input->getOption('bootstrap')
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

        [$classFilter, $methodFilter] = $this->getFilter($input);

        $paths = $this->gatherPaths($output, $input->getArgument('path'));

        foreach ($paths as $path) {
            $classes = \get_declared_classes();

            include $path;

            $classes = \array_diff(
                \get_declared_classes(),
                $classes,
                [
                    Check::class,
                ]
            );

            $classes = \array_filter(
                $classes,
                function ($class) {
                    return \preg_match('/Check$/', $class) === 1;
                }
            );

            $testClass = \array_pop($classes);

            if ($classFilter && !\fnmatch($classFilter, $testClass)) {
                continue;
            }

            $test  = new $testClass($this);
            $class = reflection()->getClass($test);

            foreach ($class->getMethods() as $method) {
                $name = $method->getName();

                if ($methodFilter && !\fnmatch($methodFilter, $name)) {
                    continue;
                }

                if (\preg_match('/^check/', $method->getName()) !== 1) {
                    continue;
                }

                $parameterCount = \count($method->getParameters());
                $tags           = $this->getMethodTags($method);

                $closure = $method->getClosure($test);

                $event = new Events\StartEvent($method, $tags);
                $dispatcher->dispatch(CheckEvents::START, $event);

                try {
                    if (!$parameterCount) {
                        \call_user_func($closure);
                    } else {
                        $noDefects  = ($input->getOption('no-defects') !== false);
                        $defectArgs = $state->getDefectArgs($method);

                        if (!$noDefects && $defectArgs) {
                            try {
                                \call_user_func($closure, ...$defectArgs);
                            } catch (InvalidArgumentException $exception) {
                                throw new ExecutionFailure($defectArgs, $exception);
                            } catch (Throwable $throwable) {
                                throw new ExecutionError($defectArgs, $throwable);
                            }
                        }

                        $result = $this->iterate(
                            $closure,
                            null,
                            $tags
                        );

                        if (!$result['passed']) {
                            throw new ExecutionFailure($result['input'], $result['error']);
                        }
                    }

                    $event = new Events\SuccessEvent($method, $tags);
                    $dispatcher->dispatch(CheckEvents::SUCCESS, $event);
                    $status = 'SUCCESS';
                } catch (ExecutionFailure $failure) {
                    $event = new Events\FailureEvent(
                        $method,
                        $tags,
                        $failure->getArgs()
                    );
                    $dispatcher->dispatch(CheckEvents::FAILURE, $event);
                    $status = 'FAILURE';
                } catch (ExecutionError $error) {
                    $event = new Events\ErrorEvent(
                        $method,
                        $tags,
                        $error->getArgs(),
                        $error->getCause()
                    );
                    $dispatcher->dispatch(CheckEvents::ERROR, $event);
                    $status = 'ERROR';
                }

                $event = new Events\EndEvent($method, $tags, $status);
                $dispatcher->dispatch(CheckEvents::END, $event);
            }
        }

        $event = new Events\EndAllEvent();
        $dispatcher->dispatch(CheckEvents::END_ALL, $event);
    }

    public function checks(
        int $size,
        callable $subject,
        callable $check = null,
        array $tags = null
    ): array {
        $reflection = reflection()->reflect($subject);
        $signature  = reflection()->getFunctionSignature($reflection);

        $check = null === $check
            ? $reflection
            : reflection()->reflect($check);

        $dispatcher = app('dispatcher');
        $result     = null;

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
                } catch (Example $e) {
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

    protected function getConfig(string $filename = null): ?SimpleXMLElement
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

    protected function gatherPaths(OutputInterface $output, string $pathArgument): array
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

    protected function getMethodTags(ReflectionMethod $method)
    {
        $factory    = DocBlockFactory::createInstance();
        $docComment = $method->getDocComment();

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

    protected function getFilter(InputInterface $input)
    {
        $filter = $input->getOption('filter');

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

    protected function shrink($args)
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

        throw new Example($args);
    }

    protected function passed(
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
                    throw new CheckError(
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
        } catch (CheckError $error) {
            $passed = false;
        }

        return [$passed, $output, $error];
    }
}
