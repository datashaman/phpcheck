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

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use SimpleXMLElement;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Throwable;
use Webmozart\Assert\Assert;

class Runner implements EventSubscriberInterface
{
    public const MAX_ITERATIONS = 100;

    protected const CONFIG_FILE = 'phpcheck.xml';

    protected $maxIterations = self::MAX_ITERATIONS;

    protected $state;

    protected $argumentFactory;

    protected $dispatcher;

    protected $input;

    protected $output;

    protected $totalIterations;

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

    public function __construct(int $seed = null)
    {
        $this->argumentFactory = new ArgumentFactory($this, $seed);
        $this->dispatcher      = new EventDispatcher();
        $this->state           = new RunState();

        $this->dispatcher->addSubscriber($this->state);
    }

    public function getGen()
    {
        return $this->argumentFactory->getGen();
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getMaxIterations()
    {
        return $this->maxIterations;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getTotalIterations()
    {
        return $this->totalIterations;
    }

    public function iterate(
        callable $callable,
        int $iterations = null
    ): void {
        $function  = new ReflectionFunction($callable);
        $arguments = $this->argumentFactory->make($function);

        $maxIterations = null === $iterations
            ? $this->maxIterations
            : $iterations;

        $iterations = 0;

        while ($arguments->valid()) {
            $args = $arguments->current();

            try {
                \call_user_func($callable, ...$args);
                $iterations++;
            } catch (InvalidArgumentException $exception) {
                $this->totalIterations += $iterations + 1;

                throw new ExecutionFailure($args, $exception);
            } catch (Throwable $throwable) {
                $this->totalIterations += $iterations + 1;

                throw new ExecutionError($args, $throwable);
            }

            if ($iterations++ >= $maxIterations - 1) {
                break;
            }

            $arguments->next();
        }

        $this->totalIterations += $iterations;
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->input  = $input;
        $this->output = $output;

        $config = $this->getConfig();

        $this->maxIterations = (int) $input->getOption('iterations');

        $this->dispatcher->addSubscriber(new Reporters\ConsoleReporter($this));

        if ($input->getOption('coverage-html') !== false) {
            $this->dispatcher->addSubscriber(new Reporters\HtmlCoverageReporter($this));
        }

        if ($input->getOption('coverage-text') !== false) {
            $this->dispatcher->addSubscriber(new Reporters\TextCoverageReporter($this));
        }

        if ($input->getOption('log-junit')) {
            $reporter = new Reporters\JUnitReporter($this);
            $this->dispatcher->addSubscriber($reporter);
        }

        $bootstrap = $input->getOption('bootstrap')
            ?: $config['bootstrap']
            ?? null;

        if ($bootstrap) {
            include_once $bootstrap;
        }

        $event = new Events\StartAllEvent();
        $this->dispatcher->dispatch(CheckEvents::START_ALL, $event);

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

            $test = new $testClass($this);

            $class = new ReflectionClass($test);

            foreach ($class->getMethods() as $method) {
                $name = $method->getName();

                if ($methodFilter && !\fnmatch($methodFilter, $name)) {
                    continue;
                }

                if (\preg_match('/^check/', $method->getName()) !== 1) {
                    continue;
                }

                $tags = $this->getMethodTags($method);

                $closure = $method->getClosure($test);

                $event = new Events\StartEvent($method);
                $this->dispatcher->dispatch(CheckEvents::START, $event);

                try {
                    if (!empty($tags['iterates'])) {
                        \call_user_func($closure);
                    } else {
                        $noDefects  = ($input->getOption('no-defects') !== false);
                        $defectArgs = $this->state->getDefectArgs($method);

                        if (!$noDefects && $defectArgs) {
                            try {
                                \call_user_func($closure, ...$defectArgs);
                            } catch (InvalidArgumentException $exception) {
                                throw new ExecutionFailure($defectArgs, $exception);
                            } catch (Throwable $throwable) {
                                throw new ExecutionError($defectArgs, $throwable);
                            }
                        }

                        $this->iterate($closure, $tags['iterations'] ?? $this->maxIterations);
                    }

                    $event = new Events\SuccessEvent($method);
                    $this->dispatcher->dispatch(CheckEvents::SUCCESS, $event);
                    $status = 'SUCCESS';
                } catch (ExecutionFailure $failure) {
                    $event = new Events\FailureEvent(
                        $method,
                        $failure->args,
                        $failure->cause
                    );
                    $this->dispatcher->dispatch(CheckEvents::FAILURE, $event);
                    $status = 'FAILURE';
                } catch (ExecutionError $error) {
                    $event = new Events\ErrorEvent(
                        $method,
                        $error->args,
                        $error->cause
                    );
                    $this->dispatcher->dispatch(CheckEvents::ERROR, $event);
                    $status = 'ERROR';
                }

                $event = new Events\EndEvent($method, $status);
                $this->dispatcher->dispatch(CheckEvents::END, $event);
            }
        }

        $event = new Events\EndAllEvent();
        $this->dispatcher->dispatch(CheckEvents::END_ALL, $event);
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

        $result = [];

        foreach ($tags as $tag) {
            $tagName = $tag->getName();

            if ($tagName === 'iterates') {
                // If a method has an @iterates tag,
                // it handles its own iteration internally
                // and should be called normally with no args.
                $result['iterates'] = true;
            } elseif ($tagName === 'iterations') {
                $result['iterations'] = (int) (string) $tag->getDescription();
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
}
