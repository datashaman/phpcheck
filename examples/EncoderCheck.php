<?php

namespace Datashaman\PHPCheck\Checks;

require_once __DIR__ . '/encoder.php';

class EncoderCheck
{
    public function checkEncode(string $str)
    {
        return $str === decode(encode($str));
    }
}
