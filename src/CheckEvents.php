<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck;

final class CheckEvents
{
    const END = 'check.end';
    const END_ALL = 'check.end_ALL';
    const ERROR = 'check.error';
    const FAILURE = 'check.failure';
    const START = 'check.start';
    const START_ALL = 'check.start_all';
    const SUCCESS = 'check.success';
}
