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

use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class Subscriber implements EventSubscriberInterface
{
    protected function getMethodSignature(ReflectionMethod $method): string
    {
        return reflection()->getMethodSignature($method);
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
