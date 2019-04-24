<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

\define('LOG', 'phpcheck.log');

function app($name, callable $f = null)
{
    static $container;

    if (!isset($container)) {
        $container = new Pimple\Container();
    }

    if (is_null($f)) {
        return $container[$name];
    }

    $container[$name] = $f;
}

function gen()
{
    return app('gen');
}

function reflection()
{
    static $reflection;

    if (!isset($reflection)) {
        $reflection = new Datashaman\PHPCheck\Reflection();
    }

    return $reflection;
}
