<?php

namespace Datashaman\PHPCheck\Checks;

use Datashaman\PHPCheck\Check;
use Webmozart\Assert\Assert;

require_once __DIR__ . '/encoder.php';

class EncoderCheck extends Check
{
    public function checkEncode(string $str)
    {
        Assert::eq($str, decode(encode($str)));
    }
}
