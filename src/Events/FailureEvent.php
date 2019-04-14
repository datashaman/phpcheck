<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Events;

class FailureEvent extends ResultEvent
{
    public $status = 'FAILURE';
}
