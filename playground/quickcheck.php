<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Datashaman\PHPCheck\Quick;

function propRevUnit ($x) {
    return array_reverse([$x]) === [$x];
}

function propRevApp (array $xs, array $ys) {
    return array_reverse(array_merge($xs, $ys)) === array_merge(array_reverse($ys), array_reverse($xs));
}

function propRevRev (array $xs) {
    return array_reverse(array_reverse($xs)) === $xs;
}

Quick::check('propRevApp');
