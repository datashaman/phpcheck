<?php

namespace Datashaman\PHPCheck;

use DateTime;
use Icecave\Repr\Generator;

class ReprGenerator extends Generator
{
    public function generate($value, $currentDepth = 0)
    {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s T');
        }

        return parent::generate($value, $currentDepth);
    }
}
