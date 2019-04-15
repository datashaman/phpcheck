<?php declare(strict_types=1);

namespace Datashaman\PHPCheck\Reporters;

use Datashaman\PHPCheck\CheckEvents;
use Datashaman\PHPCheck\Events;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Text;

class TextCoverageReporter extends Reporter
{
    protected $coverage;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END => 'onEnd',
            CheckEvents::END_ALL   => 'onEndAll',
            CheckEvents::START => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
        ];
    }

    public function onEnd(Events\EndEvent $event): void
    {
        $this->coverage->stop();
    }

    public function onEndAll(Events\EndAllEvent $event): void
    {
        $writer = new Text();
        echo $writer->process($this->coverage, true);
    }

    public function onStart(Events\StartEvent $event): void
    {
        $this->coverage->start($event->method->getName());
    }

    public function onStartAll(Events\StartAllEvent $event): void
    {
        $this->coverage = new CodeCoverage();
        $this->coverage->filter()->addDirectoryToWhitelist('./src');
    }
}
