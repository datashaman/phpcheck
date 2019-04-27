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

use Datashaman\PHPCheck\CheckEvents;
use Datashaman\PHPCheck\Events;
use function Datashaman\PHPCheck\{
    app
};
use SimpleXMLElement;

class JUnitReporter extends Subscriber
{
    private $testsuite;

    private $testcase;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END_ALL   => 'onEndAll',
            CheckEvents::FAILURE   => 'onFailure',
            CheckEvents::ERROR     => 'onError',
            CheckEvents::START     => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
        ];
    }

    public function onStartAll(): void
    {
        $this->testsuite = new SimpleXMLElement('<testsuite/>');
        $this->testcase  = null;
    }

    public function onStart(Events\StartEvent $event): void
    {
        $this->testcase              = $this->testsuite->addChild('testcase');
        $this->testcase['classname'] = $event->method->getDeclaringClass()->getName();
        $this->testcase['name']      = $event->method->getName();
    }

    public function onError(Events\ErrorEvent $event): void
    {
        $error            = $this->testcase->addChild('error');
        $error['type']    = \get_class($event->cause);
        $error['message'] = \sprintf(
            "args=%s caused error '%s'",
            \json_encode($event->args),
            $event->cause->getMessage()
        );
    }

    public function onFailure(Events\FailureEvent $event): void
    {
        $failure            = $this->testcase->addChild('failure');
        $failure['type']    = \get_class($event->cause);
        $failure['message'] = \sprintf(
            "args=%s caused failure '%s'",
            \json_encode($event->args),
            $event->cause->getMessage()
        );
    }

    public function onEndAll(): void
    {
        $this->testsuite->asXML(app('runner')->getInput()->getOption('log-junit'));
    }
}
