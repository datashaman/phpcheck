<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Reporters;

use Datashaman\PHPCheck\CheckEvents;
use Datashaman\PHPCheck\Events;
use ReflectionMethod;
use SimpleXMLElement;

class JUnitReporter extends Reporter
{
    protected $testsuite;
    protected $testcase;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END_ALL => 'onEndAll',
            CheckEvents::FAILURE => 'onFailure',
            CheckEvents::ERROR => 'onError',
            CheckEvents::START => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
        ];
    }

    public function onStartAll()
    {
        $this->testsuite = new SimpleXMLElement('<testsuite/>');
        $this->testcase = null;
    }

    public function onStart(Events\StartEvent $event)
    {
        $this->testcase = $this->testsuite->addChild('testcase');
        $this->testcase['classname'] = $event->method->getDeclaringClass()->getName();
        $this->testcase['name'] = $event->method->getName();
    }

    public function onError(Events\ErrorEvent $event)
    {
        $error = $this->testcase->addChild('error');
        $error['type'] = get_class($event->cause);
        $error['message'] = sprintf(
            "args=%s caused error '%s'",
            json_encode($event->args),
            $event->cause->getMessage()
        );
    }

    public function onFailure(Events\FailureEvent $event)
    {
        $failure = $this->testcase->addChild('failure');
        $failure['type'] = get_class($event->cause);
        $failure['message'] = sprintf(
            "args=%s caused failure '%s'",
            json_encode($event->args),
            $event->cause->getMessage()
        );
    }

    public function onEndAll(Events\EndAllEvent $event)
    {
        $this->testsuite->asXML($this->input->getOption('log-junit'));
    }
}
