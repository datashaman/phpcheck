<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Events;

class ErrorEvent extends ResultEvent
{
    public $status = 'ERROR';
}
