<?php

namespace Datashaman\PHPCheck\Checks;

require_once __DIR__ . '/encoder.php';

class EncoderCheck
{
    /**
     * @param string $str {@gen strings(ascii())}
     */
    public function checkEncode(string $str)
    {
        return $str === decode(encode($str));
    }
}
