<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    const VERSION = '0.1.0';

    protected static $defaultName = 'phpcheck';

    protected function configure()
    {
        $this
            ->setDescription('Runs checks.')
            ->addOption('bootstrap', null, InputOption::VALUE_OPTIONAL, 'A PHP script that is included before the tests run')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter the checks that will be run')
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'How many times each check will be run', Runner::MAX_ITERATIONS)
            ->addOption('log-junit', 'j', InputOption::VALUE_OPTIONAL, 'Log test execution in JUnit XML format to file')
            ->addOption('no-defects', 'd', InputOption::VALUE_OPTIONAL, 'Ignore previous defects', false)
            ->addArgument('path', InputArgument::OPTIONAL, 'File or folder with checks', 'checks');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        (new Runner($this))->execute($input, $output);
    }
}
