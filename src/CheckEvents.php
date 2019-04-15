<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * (c) Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

final class CheckEvents
{
    public const END = 'check.end';

    public const END_ALL = 'check.end_ALL';

    public const ERROR = 'check.error';

    public const FAILURE = 'check.failure';

    public const START = 'check.start';

    public const START_ALL = 'check.start_all';

    public const SUCCESS = 'check.success';
}
