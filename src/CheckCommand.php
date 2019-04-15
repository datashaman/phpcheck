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
            ->addOption('bootstrap', null, InputOption::VALUE_OPTIONAL, 'A PHP script that is included before the checks run')
            ->addOption('coverage-html', null, InputOption::VALUE_OPTIONAL, 'Generate code coverage report in HTML', false)
            ->addOption('coverage-text', null, InputOption::VALUE_OPTIONAL, 'Generate code coverage report in text', false)
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter the checks that will be run')
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'How many times each check will be run', Runner::MAX_ITERATIONS)
            ->addOption('log-junit', 'j', InputOption::VALUE_OPTIONAL, 'Log check execution in JUnit XML format to file')
            ->addOption('no-defects', 'd', InputOption::VALUE_OPTIONAL, 'Ignore previous defects', false)
            ->addOption('seed', 's', InputOption::VALUE_OPTIONAL, 'Seed the random number generator to get repeatable runs')
            ->addArgument('path', InputArgument::OPTIONAL, 'File or folder with checks', 'checks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $seed = $input->getOption('seed');

        if ($seed) {
            $seed = (int) $seed;
        }

        (new Runner($seed))->execute($input, $output);

        return null;
    }
}
