<?php

function encode($inputString) {
    if (!$inputString) {
        return [];
    }

    $count = 1;
    $prev = '';
    $lst = [];

    foreach (str_split($inputString) as $character) {
        if ($character != $prev) {
            if ($prev) {
                $entry = [$prev, $count];
                $lst[] = $entry;
            }
            $count = 1;
            $prev = $character;
        } else {
            $count++;
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
