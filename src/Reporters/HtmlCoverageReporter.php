<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Reporters;

use Datashaman\PHPCheck\CheckEvents;
use Datashaman\PHPCheck\Events;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlFacade;

class HtmlCoverageReporter extends Reporter
{
    protected $coverage;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::END       => 'onEnd',
            CheckEvents::END_ALL   => 'onEndAll',
            CheckEvents::START     => 'onStart',
            CheckEvents::START_ALL => 'onStartAll',
        ];
    }

    public function onEnd(Events\EndEvent $event): void
    {
        $this->coverage->stop();
    }

    public function onEndAll(Events\EndAllEvent $event): void
    {
        $writer = new HtmlFacade();
        $writer->process($this->coverage, $this->input->getOption('coverage-html'));
    }

    public function onStart(Events\StartEvent $event): void
    {
        $this->coverage->start($event->method->getName());
    }

    public function onStartAll(Events\StartAllEvent $event): void
    {
        if (!$this->input->getOption('coverage-html')) {
            $this->output->writeln('<error>You must specify a folder with --coverage-html</error>');
            exit(1);
        }

        $this->coverage = new CodeCoverage();
        $this->coverage->filter()->addDirectoryToWhitelist('./src');
    }
}
