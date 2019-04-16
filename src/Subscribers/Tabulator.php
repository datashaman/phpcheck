<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Subscribers;

use Datashaman\PHPCheck\CheckEvents;
use Datashaman\PHPCheck\Events;
use Datashaman\PHPCheck\Traits\LogTrait;
use Ds\Map;
use Exception;

class Tabulator extends Subscriber
{
    use LogTrait;

    protected $names;
    protected $tables;
    protected $stats;

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

    public function evalTag($expression, $args = [])
    {
        extract($args);
        $expression = "return $expression;";

        return eval($expression);
    }

    public function onIteration(Events\IterationEvent $event): void
    {
        if ($event->tags['tabulate'] ?? false) {
            foreach ($event->tags['tabulate'] as $label => $expression) {
                $args = array_combine(
                    $this->names,
                    $event->args
                );

                $values = $this->evalTag($expression, $args);

                if (!is_array($values)) {
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
                'tags' => $event->tags,
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
                $output->writeln($index+1 . ') ' . $this->getMethodSignature($method));
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

                    $cover = new Map();
                    $warnings = [];

                    if (isset($tags['coverTable'][$label])) {
                        $expression = $tags['coverTable'][$label];
                        $results = $this->evalTag($expression);
                        foreach ($results as $result) {
                            [$value, $percentage] = $result;
                            $cover->put($value, $percentage);
                        }
                    }

                    foreach ($stats as $value => $percentage) {
                        $output->writeln(
                            sprintf('%5s%% %s',
                                round($percentage, 1),
                                preg_replace(
                                    '/(^\[|\]$)/',
                                    '',
                                    $this->repr($value)
                                )
                            )
                        );

                        if ($cover->hasKey($value)) {
                            $expected = $cover[$value];

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
                                sprintf(
                                    "Table '%s' had only %.1f%% %s, but expected %.1f%%",
                                    $label,
                                    $percentage,
                                    $this->repr($value),
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
