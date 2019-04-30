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

use Datashaman\PHPCheck\Subscribers\ConsoleReporter;
use Datashaman\PHPCheck\Subscribers\Tabulator;
use Dotenv\Dotenv;
use Faker\Factory;
use Icecave\Repr\Generator;
use Icecave\Repr\Repr;
use SQLite3;
use Symfony\Component\EventDispatcher\EventDispatcher;

$dotenv = Dotenv::create(\getcwd());
$dotenv->load();

\mb_regex_encoding('UTF-8');
\mb_internal_encoding('UTF-8');

Repr::install(new Generator(50, 3, 12));

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

app('random', function ($c) {
    return new Random();
});

app('reflection', function ($c) {
    return new Reflection();
});

app('runner', function ($c) {
    $runner = new Runner();

    $c['dispatcher']->addSubscriber($c['state']);
    $c['dispatcher']->addSubscriber($c['tabulator']);
    $c['dispatcher']->addSubscriber(new ConsoleReporter());

    return $runner;
});

app('state', function ($c) {
    return new RunState();
});

app('tabulator', function ($c) {
    return new Tabulator();
});
