<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * (c) Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

use ReflectionMethod;
use SQLite3;
use SQLite3Result;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RunState implements EventSubscriberInterface
{
    public const DATABASE_DIR = '.phpcheck';

    public const CREATE_RESULTS_SQL = <<<'EOT'
CREATE TABLE IF NOT EXISTS results (
    class TEXT NOT NULL,
    method TEXT NOT NULL,
    status TEXT NOT NULL,
    time TEXT NOT NULL,
    args TEXT NOT NULL,
    PRIMARY KEY (class, method)
)
EOT;

    public const INSERT_RESULT_SQL = <<<'EOT'
INSERT OR REPLACE INTO results (
    class,
    method,
    status,
    time,
    args
) VALUES (
    :class,
    :method,
    :status,
    :time,
    :args
)
EOT;

    public const SELECT_DEFECT_SQL = <<<'EOT'
SELECT args FROM results WHERE class = :class AND method = :method AND status IN ('ERROR', 'FAILURE')
EOT;

    public $errors;

    public $failures;

    public $startTime;

    public $successes;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::FAILURE   => 'onFailure',
            CheckEvents::ERROR     => 'onError',
            CheckEvents::START_ALL => 'onStartAll',
            CheckEvents::SUCCESS   => 'onSuccess',
        ];
    }

    public function onError(Events\ErrorEvent $event): void
    {
        $this->errors[] = $event;
        $this->saveResult($event);
    }

    public function onFailure(Events\FailureEvent $event): void
    {
        $this->failures[] = $event;
        $this->saveResult($event);
    }

    public function onStartAll(Events\StartAllEvent $event): void
    {
        $this->errors    = [];
        $this->failures  = [];
        $this->successes = [];
        $this->startTime = $event->time;
    }

    public function onSuccess(Events\SuccessEvent $event): void
    {
        $this->successes[] = $event;
        $this->saveResult($event);
    }

    public function getDefectArgs(ReflectionMethod $method): ?array
    {
        $db     = $this->getResultsDatabase();

        $statement = $db->prepare(self::SELECT_DEFECT_SQL);
        $statement->bindValue(':class', $method->getDeclaringClass()->getName(), SQLITE3_TEXT);
        $statement->bindValue(':method', $method->getName(), SQLITE3_TEXT);

        $result = $statement->execute();

        if (!$result instanceof SQLite3Result) {
            return null;
        }

        $failure = $result->fetchArray();

        if ($failure) {
            $args = $failure['args'];

            return (array) \json_decode($args, true);
        }

        return null;
    }

    protected function saveResult(Events\ResultEvent $event): void
    {
        $args = $event->args ? (\json_encode($event->args) ?: '') : '';

        $db = $this->getResultsDatabase();

        $statement = $db->prepare(self::INSERT_RESULT_SQL);
        $statement->bindValue(':class', $event->method->getDeclaringClass()->getName(), SQLITE3_TEXT);
        $statement->bindValue(':method', $event->method->getName(), SQLITE3_TEXT);
        $statement->bindValue(':status', $event->status, SQLITE3_TEXT);
        $statement->bindValue(':time', $this->formatMicrotime($event->time));
        $statement->bindValue(':args', $args);

        $statement->execute();
    }

    protected function formatMicrotime(float $microtime): string
    {
        if (\preg_match('/^[0-9]*\\.([0-9]+)$/', (string) $microtime, $reg)) {
            $decimal = \mb_substr(\str_pad($reg[1], 6, '0'), 0, 6);
        } else {
            $decimal = '000000';
        }

        $format = \preg_replace('/(%f)/', $decimal, '%F %T.%f');

        return \strftime($format, (int) $microtime);
    }

    protected function getResultsDatabase(): SQLite3
    {
        static $db;

        if (!isset($db)) {
            if (!\is_dir(self::DATABASE_DIR)) {
                \mkdir(self::DATABASE_DIR, 0755);
            }

            $db = new SQLite3(self::DATABASE_DIR . \DIRECTORY_SEPARATOR . 'results.db');
            $db->exec(self::CREATE_RESULTS_SQL);
        }

        return $db;
    }
}
