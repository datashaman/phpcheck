<?php

declare(strict_types=1);

namespace Datashaman\PHPCheck\Events;

class SuccessEvent extends ResultEvent
{
    public $status = 'SUCCESS';
}
