<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * (c) Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

class Check
{
    /**
     * @var Runner
     */
    protected $runner;

    /**
     * @var Gen
     */
    protected $gen;

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
        $this->gen    = $runner->getGen();
    }
}
