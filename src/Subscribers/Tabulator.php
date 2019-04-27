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
use Datashaman\PHPCheck\CheckEvents;
use function Datashaman\PHPCheck\evalWithArgs;
use Datashaman\PHPCheck\Events;

use function Datashaman\PHPCheck\reflection;
use function Datashaman\PHPCheck\repr;
use Ds\Map;
use Exception;

class Tabulator extends Subscriber
{
    private $names;

    private $tables;

    private $stats;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END_ALL   => 'onEndAll',
            CheckEvents::END       => 'onEnd',
            CheckEvents::START     => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
        ];
    }

    public function onStartAll(Events\StartAllEvent $event): void
    {
        $this->tables = new Map();
    }

    public function onStart(Events\StartEvent $event): void
    {
        if ($event->tags['tabulate'] ?? false) {
            $this->stats = new Map();
            $this->names = [];

            foreach ($event->method->getParameters() as $param) {
                $this->names[$param->getPosition()] = $param->getName();
            }

            app('dispatcher')->addListener(CheckEvents::ITERATION, [$this, 'onIteration']);
        }
    }

    public function onIteration(Events\IterationEvent $event): void
    {
        if ($event->tags['tabulate'] ?? false) {
            foreach ($event->tags['tabulate'] as $label => $expression) {
                $args = \array_combine(
                    $this->names,
                    $event->args
                );

                $values = evalWithArgs($expression, $args);

                if (!\is_array($values)) {
                    throw new Exception('tabulate expression must return an array of values');
                }

                if (!$this->stats->hasKey($label)) {
                    $this->stats->put($label, new Map());
                }

                $labelStats = $this->stats[$label];

                foreach ($values as $value) {
                    $count = $labelStats->hasKey($value) ? $labelStats->get($value) : 0;
                    $labelStats->put($value, $count + 1);
                }
            }
        }
    }

    public function onEnd(Events\EndEvent $event): void
    {
        if ($event->tags['tabulate'] ?? false) {
            app('dispatcher')->removeListener(CheckEvents::ITERATION, [$this, 'onIteration']);

            $tables = $this
                ->stats
                ->map(
                    function ($label, $stats) {
                        return $stats->sorted(
                            function ($a, $b) {
                                return $b <=> $a;
                            }
                        );
                    }
                );

            $this->tables->put($event->method, [
                'tables' => $tables,
                'tags'   => $event->tags,
            ]);
        }
    }

    public function onEndAll(Events\EndAllEvent $event): void
    {
        if (!$this->tables->isEmpty()) {
            $output = app('runner')->getOutput();
            $output->writeln('');
            $output->writeln('');
            $output->writeln('Tables');
            $output->writeln('');

            foreach ($this->tables->keys() as $index => $method) {
                $output->writeln($index + 1 . ') ' . reflection()->getMethodSignature($method));
                $output->writeln('');

                ['tables' => $tables, 'tags' => $tags] = $this->tables->get($method);

                foreach ($tables as $label => $stats) {
                    $total = $stats->sum();

                    $output->writeln($label . ' (' . $total . ' total)');
                    $output->writeln('');

                    if (!$total) {
                        continue;
                    }

                    $stats = $stats->map(
                        function ($value, $count) use ($total) {
                            return $count / $total * 100;
                        }
                    );

                    $cover    = new Map();
                    $warnings = [];

                    if (isset($tags['coverTable'][$label])) {
                        $expression = $tags['coverTable'][$label];
                        $results    = evalWithArgs($expression);

                        foreach ($results as $result) {
                            [$value, $percentage] = $result;
                            $cover->put($value, $percentage);
                        }
                    }

                    foreach ($stats as $value => $percentage) {
                        $output->writeln(
                            \sprintf(
                                '%5s%% %s',
                                \round($percentage, 1),
                                \preg_replace(
                                    '/(^\[|\]$)/',
                                    '',
                                    repr($value)
                                )
                            )
                        );

                        if ($cover->hasKey($value)) {
                            $expected = $cover->get($value);

                            if ($percentage < $expected) {
                                $warnings[] = [$value, $expected, $percentage];
                            }
                        }
                    }

                    if ($warnings) {
                        $output->writeln('');

                        foreach ($warnings as $warning) {
                            [$value, $expected, $percentage] = $warning;

                            $output->writeln(
                                \sprintf(
                                    "Table '%s' had only %.1f%% %s, but expected %.1f%%",
                                    $label,
                                    $percentage,
                                    repr($value),
                                    $expected
                                )
                            );
                        }
                    }

                    $output->writeln('');
                }
            }
        }
    }
}
