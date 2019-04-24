<?php

error_reporting(E_ALL);
ini_set('scream.enabled', true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'encoder.php';

$results = gen()
    ->checks(100, 'encode', function ($input, $output) {
        $result = $input[0] === decode($output);

        return $result;
    });

foreach ($results as $result) {
    if (!$result['passed']) {
        echo "FAILURE!\n";
        dd($result);
        exit();
    }
}

echo "OK!\n";
