<?php

function encode(string $inputString) {
    // This will return [] for '' and '0'
    if (!$inputString) {
        return [];
    }

    $count = 1;
    // Set default to null
    $prev = '';
    $lst = [];

    foreach (preg_split('//u', $inputString, null, PREG_SPLIT_NO_EMPTY) as $character) {
        if ($character != $prev) {
            // Use !is_null instead
            if (!$prev) {
                $entry = [$prev, $count];
                $lst[] = $entry;
            }
            $count = 1;
            $prev = $character;
        } else {
            // Missing reset operation
            // $count++;
        }
    }

    $entry = [$character, $count];
    $lst[] = $entry;

    return $lst;
}

function decode($lst) {
    $q = '';
    foreach ($lst as $entry) {
        [$character, $count] = $entry;
        $q .= str_repeat($character, $count);
    }

    return $q;
}
