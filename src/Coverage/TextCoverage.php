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

use SebastianBergmann\CodeCoverage\Report\Text;

/**
 * This class produces a text coverage report to standard output or a specified file.
 */
class TextCoverage extends Coverage
{
    /**
     * Processing is done in the __destruct method to ensure maximum coverage
     * results.
     */
    public function __destruct()
    {
        global $coverage;

        parent::__destruct();

        $writer = new Text();

        if ($this->input->getOption('coverage-text')) {
            $output = $writer->process($coverage, false);
            \file_put_contents($this->input->getOption('coverage-text'), $output);

            return;
        }

        $color = true;

        if ($this->input->getOption('no-ansi') !== false) {
            $color = false;
        }

        print $writer->process($coverage, $color);
    }
}
