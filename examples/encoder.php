<?php

/**
 * @param string $inputString {@gen #strings #ascii}
 */
function encode(string $inputString) {
    if ($inputString === '') {
        return [];
    }

    $count = 1;
    $prev = null;
    $lst = [];

    foreach (preg_split('//u', $inputString, null, PREG_SPLIT_NO_EMPTY) as $character) {
        if ($character != $prev) {
            if (!is_null($prev)) {
                $entry = [$prev, $count];
                $lst[] = $entry;
            }
            $count = 1;
            $prev = $character;
        } else {
            // Missing reset operation
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
