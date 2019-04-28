<?php declare(strict_types=1);
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
use Datashaman\PHPCheck\Events;

class TextReporter extends Subscriber
{
    protected $file;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::START_ALL => 'onStartAll',
            CheckEvents::START     => 'onStart',
            CheckEvents::SUCCESS   => 'onSuccess',
            CheckEvents::ERROR     => 'onError',
            CheckEvents::FAILURE   => 'onFailure',
            CheckEvents::END_ALL   => 'onEndAll',
        ];
    }

    public function __call(string $name, array $args): void
    {
        $this->report($name, ...$args);
    }

    public function onStartAll(Events\StartAllEvent $event): void
    {
        $this->file = \fopen(app('runner')->getInput()->getOption('log-text'), 'a');
        $this->report('onStartAll', $event);
    }

    public function onEndAll(Events\EndAllEvent $event): void
    {
        $this->report('onEndAll', $event);

        if (\is_resource($this->file)) {
            \fclose($this->file);
        }
    }

    protected function report(string $name, Events\Event $event): void
    {
        $shortName = \preg_replace(
            '/Event$/',
            '',
            app('reflection')->getClass($event)->getShortName()
        );
        $message = \sprintf(
            '%s [%-10s]',
            \strftime('%F %T', (int) $event->time),
            \mb_strtoupper($shortName)
        );

        if (
            \in_array(
                $name,
                [
                    'onError',
                    'onFailure',
                    'onStart',
                    'onSuccess',
                ]
            )
        ) {
            $message .= ' ' . app('reflection')->getMethodSignature($event->function);
        }

        if (
            $event instanceof Events\ResultEvent
        ) {
            if (null !== $event->args) {
                $args = \preg_replace(
                    [
                        '/^\[/',
                        '/\]$/',
                    ],
                    '',
                    \json_encode($event->args)
                );

                $message .= '(' . $args . ')';
            }

            if ($event->cause) {
                $message .= ' caused ' . \get_class($event->cause) . '("' . $event->cause->getMessage() . '")';
            }
        }

        $message .= "\n";

        \fwrite($this->file, $message);
    }
}
