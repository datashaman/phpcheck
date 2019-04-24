<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Datashaman\PHPCheck\MkGen;
use Datashaman\PHPCheck\Runner;
use Datashaman\PHPCheck\RunState;
use Datashaman\PHPCheck\Subscribers\ConsoleReporter;
use Datashaman\PHPCheck\Subscribers\Tabulator;
use Datashaman\PHPCheck\TagParser;
use Dotenv\Dotenv;
use Faker\Factory;
use Pimple\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dotenv = Dotenv::create(__DIR__ . \DIRECTORY_SEPARATOR . '..');
$dotenv->load();

mb_regex_encoding('UTF-8');
mb_internal_encoding('UTF-8');

app('database', function ($c) {
    if (!\is_dir(RunState::DATABASE_DIR)) {
        \mkdir(RunState::DATABASE_DIR, 0755);
    }

    $database = new SQLite3(RunState::DATABASE_DIR . \DIRECTORY_SEPARATOR . 'results.db');
    $database->exec(RunState::CREATE_RESULTS_SQL);

    return $database;
});

app('dispatcher', function ($c) {
    return new EventDispatcher();
});

app('faker', function ($c) {
    return Factory::create();
});

app('gen', function ($c) {
    $gen = new MkGen();
    $gen->setSeed(null);

    return $gen;
});

app('runner', function ($c) {
    $runner = new Runner();

    $c['dispatcher']->addSubscriber($c['state']);
    $c['dispatcher']->addSubscriber($c['tabulator']);
    $c['dispatcher']->addSubscriber(new ConsoleReporter($runner));

    return $runner;
});

app('state', function ($c) {
    return new RunState();
});

app('tabulator', function ($c) {
    return new Tabulator();
});

app('tagParser', function ($c) {
    return new TagParser();
});
