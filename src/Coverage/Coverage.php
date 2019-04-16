<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck\Coverage;

use Datashaman\PHPCheck\CheckEvents;
use Datashaman\PHPCheck\Events;
use Datashaman\PHPCheck\Runner;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Text;

abstract class Coverage
{
    protected $input;

    public function __construct(Runner $runner)
    {
        $this->input = $runner->getInput();
    }

    public function __destruct()
    {
        global $coverage;

        $coverage->stop();
    }
}
