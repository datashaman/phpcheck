<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

class Args
{
    public $bootstrap;

    public $coverageHtml = false;

    public $coverageText = false;

    public $filter;

    public $logJunit = false;

    public $logText = false;

    public $maxSuccess = Runner::MAX_SUCCESS;

    public $noAnsi;

    public $noDefects;

    public $path;

    public $output;

    public $subject;
}
