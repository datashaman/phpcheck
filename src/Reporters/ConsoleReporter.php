<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Reporters;

use Datashaman\PHPCheck\CheckCommand;
use Datashaman\PHPCheck\CheckEvents;
use Datashaman\PHPCheck\Events;
use Datashaman\PHPCheck\Runner;
use NunoMaduro\Collision\Writer;
use Whoops\Exception\Inspector;

class ConsoleReporter extends Reporter
{
    const HEADER = 'PHPCheck %s by Marlin Forbes and contributors.';

    const STATUS_CHARACTERS = [
        'FAILURE' => 'F',
        'ERROR' => 'E',
        'SUCCESS' => '.',
    ];

    const STATUS_FORMATS = [
        'FAILURE' => 'error',
        'ERROR' => 'error',
        'SUCCESS' => 'info',
    ];

    protected $writer;

    public function __construct(Runner $runner)
    {
        parent::__construct($runner);

        $baseDir = realpath(__DIR__ . '/../');

        $this->writer = new Writer();
        $this->writer->ignoreFilesIn(
            [
                '#' . $baseDir . '/src/*#',
                '#' . $baseDir . '/bin/*#',
                '#vendor/symfony/console.*#',
                '#vendor/webmozart/assert.*#',
            ]
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END_ALL => 'onEndAll',
            CheckEvents::FAILURE => 'onFailure',
            CheckEvents::END => 'onEnd',
            CheckEvents::ERROR => 'onError',
            CheckEvents::START => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
        ];
    }

    public function onStartAll(Events\StartAllEvent $event): void
    {
        $this->output->writeln(
            sprintf(self::HEADER, CheckCommand::VERSION)
        );
        $this->output->writeln('');
    }

    public function onStart(Events\StartEvent $event): void
    {
        if ($this->output->isDebug()) {
            $signature = $this->getMethodSignature($event->method);
            $this->output->writeln("Check '$signature' started");
        }
    }

    public function onEnd(Events\EndEvent $event): void
    {
        if ($this->output->isDebug()) {
            $signature = $this->getMethodSignature($event->method);
            $this->output->writeln("Check '$signature' ended");
        } else {
            $char = self::STATUS_CHARACTERS[$event->status];
            $format = self::STATUS_FORMATS[$event->status];
            $this->output->write("<$format>$char</$format>");
        }
    }

    public function onEndAll(Events\EndAllEvent $event): void
    {
        $successes = count($this->state->successes);
        $total = $successes + count($this->state->failures);

        if (!$this->output->isDebug()) {
            $percentage = $total ? (int) ($successes / $total * 100) : 0;

            $this->output->writeln('');
            $this->output->writeln('');
            $this->output->writeln("$successes / $total ($percentage%)");
        }

        $seconds = $event->time - $this->state->startTime;

        if ($seconds < 1) {
            $time = (int) ($seconds * 1000) . ' ms';
        } else {
            $time = round($seconds, 2) . ' seconds';
        }

        $memory = $this->convertBytes(memory_get_peak_usage(true));

        $this->output->writeln('');
        $this->output->writeln("Time: $time, Memory: $memory");

        if ($failures = count($this->state->failures)) {
            $this->writer->setOutput($this->output);

            $message = $failures === 1 ? 'There was 1 failure:' : "There were $failures failures:";

            $this->output->writeln('');
            $this->output->writeln($message);
            $this->output->writeln('');

            foreach ($this->state->failures as $index => $failure) {
                $number = $index + 1;
                $signature = $this->getMethodSignature($failure->method);
                $this->output->writeln("$number) $signature");

                $inspector = new Inspector($failure->cause);
                $this->writer->write($inspector);
                $this->output->writeln('');
            }
        }

        if ($errors = count($this->state->errors)) {
            $this->writer->setOutput($this->output);

            $message = $errors === 1 ? 'There was 1 error:' : "There were $errors errors:";

            $this->output->writeln('');
            $this->output->writeln($message);
            $this->output->writeln('');

            foreach ($this->state->errors as $index => $error) {
                $number = $index + 1;
                $signature = $this->getMethodSignature($error->method);
                $this->output->writeln("$number) $signature");

                $inspector = new Inspector($error->cause);
                $this->writer->write($inspector);
                $this->output->writeln('');
            }
        }

        $this->output->writeln('');

        $stats = "(Checks: $total, Iterations: {$this->runner->getTotalIterations()}, Failures: $failures, Errors: $errors)";
        $this->output->writeln($failures ? "<error>FAILURES $stats</error>" : "<info>OK $stats</info>");
    }
}
