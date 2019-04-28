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

use ReflectionFunctionAbstract;
use ReflectionMethod;
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

    private const INSERT_RESULT_SQL = <<<'EOT'
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

    private const SELECT_DEFECT_SQL = <<<'EOT'
SELECT args FROM results WHERE class = :class AND method = :method AND status IN ('ERROR', 'FAILURE')
EOT;

    private $errors = [];

    private $failures = [];

    private $startTime;

    private $successes = [];

    public static function getSubscribedEvents(): array
    {
        return [
            CheckEvents::ERROR     => 'onError',
            CheckEvents::FAILURE   => 'onFailure',
            CheckEvents::START_ALL => 'onStartAll',
            CheckEvents::SUCCESS   => 'onSuccess',
        ];
    }

    public function getDefectArgs(ReflectionFunctionAbstract $function): ?array
    {
        $statement = app('database')->prepare(self::SELECT_DEFECT_SQL);

        $statement->bindValue(
            ':class',
            $function instanceof ReflectionMethod
                ? $function->getDeclaringClass()->getName()
                : '',
            \SQLITE3_TEXT
        );
        $statement->bindValue(':method', $function->getName(), \SQLITE3_TEXT);

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

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
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

    private function saveResult(Events\ResultEvent $event): void
    {
        $args = $event->args ? (\json_encode($event->args) ?: '') : '';

        $statement = app('database')->prepare(self::INSERT_RESULT_SQL);

        $statement->bindValue(
            ':class',
            $event->function instanceof ReflectionMethod
                ? $event->function->getDeclaringClass()->getName()
                : '',
            \SQLITE3_TEXT
        );
        $statement->bindValue(':method', $event->function->getName(), \SQLITE3_TEXT);
        $statement->bindValue(':status', $event->status, \SQLITE3_TEXT);
        $statement->bindValue(':time', $this->formatMicrotime($event->time));
        $statement->bindValue(':args', $args);

        $statement->execute();
    }

    private function formatMicrotime(float $microtime): string
    {
        $decimal = \preg_match('/^[0-9]*\\.([0-9]+)$/', (string) $microtime, $reg)
            ? \mb_substr(\str_pad($reg[1], 6, '0'), 0, 6)
            : '000000';

        $format = \preg_replace('/(%f)/', $decimal, '%F %T.%f');

        return \strftime($format, (int) $microtime);
    }
}
