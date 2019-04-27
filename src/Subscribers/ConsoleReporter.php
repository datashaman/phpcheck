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
namespace Datashaman\PHPCheck\Subscribers;

use function Datashaman\PHPCheck\app;
use Datashaman\PHPCheck\CheckCommand;
use Datashaman\PHPCheck\CheckEvents;

use Datashaman\PHPCheck\Events;
use function Datashaman\PHPCheck\reflection;
use function Datashaman\PHPCheck\repr;
use NunoMaduro\Collision\Writer;
use Whoops\Exception\Inspector;

class ConsoleReporter extends Subscriber
{
    protected const HEADER = 'PHPCheck %s by Marlin Forbes and contributors.';

    protected const STATUS_CHARACTERS = [
        'FAILURE' => 'F',
        'ERROR'   => 'E',
        'SUCCESS' => '.',
    ];

    protected const STATUS_FORMATS = [
        'FAILURE' => 'error',
        'ERROR'   => 'error',
        'SUCCESS' => 'info',
    ];

    private $output;

    private $writer;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END_ALL   => 'onEndAll',
            CheckEvents::END       => 'onEnd',
            CheckEvents::START     => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
        ];
    }

    public function __construct()
    {
        $baseDir = \realpath(__DIR__ . '/../');

        $this->writer = new Writer();
        $this->writer->ignoreFilesIn(
            [
                '#' . $baseDir . '/src/*#',
                '#' . $baseDir . '/bin/*#',
                '#vendor/symfony/console.*#',
            ]
        );
    }

    public function onStartAll(Events\StartAllEvent $event): void
    {
        $this->output = app('runner')->getOutput();

        $this->output->writeln(
            \sprintf(self::HEADER, CheckCommand::VERSION)
        );
        $this->output->writeln('');
    }

    public function onStart(Events\StartEvent $event): void
    {
        if ($this->output->isDebug()) {
            $signature = reflection()->getMethodSignature($event->method);
            $this->output->writeln("Check '$signature' started");
        }
    }

    public function onEnd(Events\EndEvent $event): void
    {
        if ($this->output->isDebug()) {
            $signature = reflection()->getMethodSignature($event->method);
            $this->output->writeln("Check '$signature' ended");

            return;
        }

        $char   = self::STATUS_CHARACTERS[$event->status];
        $format = self::STATUS_FORMATS[$event->status];
        $this->output->write("<$format>$char</$format>");
    }

    public function onEndAll(Events\EndAllEvent $event): void
    {
        $state = app('state');

        $errors    = $state->getErrors();
        $failures  = $state->getFailures();
        $successes = $state->getSuccesses();

        $successCount = \count($successes);
        $errorCount   = \count($errors);
        $failureCount = \count($failures);

        $totalCount = $successCount + $errorCount + $failureCount;

        if (!$this->output->isDebug()) {
            $percentage = $totalCount ? (int) ($successCount / $totalCount * 100) : 0;

            $this->output->writeln('');
            $this->output->writeln('');
            $this->output->writeln("$successCount / $totalCount ($percentage%)");
        }

        $seconds = $event->time - $state->getStartTime();

        if ($seconds < 1) {
            $time = (int) ($seconds * 1000) . ' ms';
        } else {
            $time = \round($seconds, 2) . ' seconds';
        }

        $memory = $this->convertBytes(\memory_get_peak_usage(true));

        $this->output->writeln('');
        $this->output->writeln("Time: $time, Memory: $memory");

        if ($failureCount) {
            $this->writer->setOutput($this->output);

            $message = $failureCount === 1 ? 'There was 1 failure:' : "There were $failureCount failures:";

            $this->output->writeln('');
            $this->output->writeln($message);
            $this->output->writeln('');

            foreach ($failures as $index => $failure) {
                $number    = $index + 1;
                $signature = reflection()->getMethodSignature($failure->method);
                $this->output->writeln("$number) $signature");

                $this->output->writeln('');
                $this->output->writeln('ARGS: ' . repr($failure->args));
                $this->output->writeln('');

                // $inspector = new Inspector($failure->cause);
                // $this->writer->write($inspector);
                // $this->output->writeln('');
            }
        }

        if ($errorCount) {
            $this->writer->setOutput($this->output);

            $message = $errorCount === 1 ? 'There was 1 error:' : "There were $errorCount errors:";

            $this->output->writeln('');
            $this->output->writeln($message);
            $this->output->writeln('');

            foreach ($errors as $index => $error) {
                $number    = $index + 1;
                $signature = reflection()->getMethodSignature($error->method);
                $this->output->writeln("$number) $signature");

                $inspector = new Inspector($error->cause);
                $this->writer->write($inspector);
                $this->output->writeln('');
            }
        }

        $this->output->writeln('');

        $iterations = app('runner')->getTotalIterations();

        $stats = "(Checks: $totalCount, Iterations: $iterations, Failures: $failureCount, Errors: $errorCount)";
        $this->output->writeln(
            ($failureCount || $errorCount)
                ? "<error>DEFECTS $stats</error>"
                : "<info>OK $stats</info>"
        );
    }

    private function convertBytes(int $bytes): string
    {
        if ($bytes == 0) {
            return '0.00 B';
        }

        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $exponent = (int) \floor(\log($bytes, 1024));

        return \sprintf('%.2f %s', \round($bytes / 1024 ** $exponent, 2), $suffixes[$exponent]);
    }
}
