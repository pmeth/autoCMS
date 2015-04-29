<?php

function get($file, $id, $secondary = null) {
    $dataFile = 'admin/data/' . $file;
    $json = json_decode(file_get_contents($dataFile), true);

    if (!is_null($secondary) && $json[$id]['type'] == 'repeat') {
        return $json[$id]['repeat'][$secondary][$json[$secondary]['type']];
    }
    return $json[$id][$json[$id]['type']];
}