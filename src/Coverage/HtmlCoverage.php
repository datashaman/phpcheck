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

use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlFacade;

/**
 * This class produces an HTML coverage report to a specified folder.
 */
class HtmlCoverage extends Coverage
{
    /**
     * Processing is done in the __destruct method to ensure maximum coverage
     * results.
     */
    public function __destruct()
    {
        global $coverage;

        parent::__destruct();

        $writer = new HtmlFacade();
        $writer->process($coverage, $this->input->getOption('coverage-html'));
    }
}
