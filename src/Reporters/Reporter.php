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

namespace Datashaman\PHPCheck\Reporters;

use Datashaman\PHPCheck\Runner;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class Reporter implements EventSubscriberInterface
{
    protected $input;

    protected $output;

    protected $runner;

    protected $state;

    public function __construct(Runner $runner)
    {
        $this->input  = $runner->getInput();
        $this->output = $runner->getOutput();
        $this->runner = $runner;
        $this->state  = $runner->getState();
    }

    protected function getMethodSignature(ReflectionMethod $method): string
    {
        return $method->getDeclaringClass()->getName() . '::' . $method->getName();
    }

    protected function convertBytes(int $bytes): string
    {
        if ($bytes == 0) {
            return '0.00 B';
        }

        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $exponent = (int) \floor(\log($bytes, 1024));

        return \sprintf('%.2f %s', \round($bytes / 1024 ** $exponent, 2), $suffixes[$exponent]);
    }
}
