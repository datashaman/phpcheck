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
namespace Datashaman\PHPCheck;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    public const VERSION = '0.1.0';

    protected static $defaultName = 'phpcheck';

    protected function configure(): void
    {
        $this
            ->setDescription('Run checks.')
            ->addOption(
                'bootstrap',
                null,
                InputOption::VALUE_OPTIONAL,
                'A PHP script that is included before the checks run'
            )
            ->addOption(
                'coverage-html',
                null,
                InputOption::VALUE_OPTIONAL,
                'Generate HTML code coverage report',
                false
            )
            ->addOption(
                'coverage-text',
                null,
                InputOption::VALUE_OPTIONAL,
                'Generate text code coverage report',
                false
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Filter the checks that will be run'
            )
            ->addOption(
                'log-junit',
                'j',
                InputOption::VALUE_OPTIONAL,
                'Log check execution to JUnit XML file',
                false
            )
            ->addOption(
                'log-text',
                't',
                InputOption::VALUE_OPTIONAL,
                'Log check execution to text file',
                false
            )
            ->addOption(
                'max-discard-ratio',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of discarded checks per successful check before giving up',
                Runner::MAX_DISCARD_RATIO
            )
            ->addOption(
                'max-shrinks',
                null,
                InputOption::VALUE_REQUIRED,
                "Maximum number of shrinks to before giving up.\nSetting this to zero turns shrinking off.",
                Runner::MAX_SIZE
            )
            ->addOption(
                'max-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Size to use for the biggest test cases',
                Runner::MAX_SIZE
            )
            ->addOption(
                'max-success',
                null,
                InputOption::VALUE_REQUIRED,
                "Maximum number of successful checks before succeeding. Testing stops at the first failure.\n" .
                'If all tests are passing and you want to run more tests, increase this number.',
                Runner::MAX_SUCCESS
            )
            ->addOption(
                'no-defects',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Ignore previous defects',
                false
            )
            ->addOption(
                'replay',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Replay execution with a specific seed'
            )
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'File or folder with checks',
                'checks'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $result = app('runner')->execute($input, $output);

        // TODO Return code

        return null;
    }
}
