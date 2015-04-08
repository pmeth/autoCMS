<?php

function scanFiles($endsWith) {
    $files = scandir('../');
    $arr = Array();
    foreach ($files as $file) {
        if (endsWith($file, $endsWith)) $arr[] = $file;
    }
    return $arr;
}

function authNeeded() {
    $json = json_decode(file_get_contents("data/access.json"), true);
    return sizeof($json) === 0;
}

function checkPass($user = null, $pass = null) {
    if (is_null($user)) $user = $_SESSION["user"];
    if (is_null($pass)) $pass = $_SESSION["password"];
    $json = json_decode(file_get_contents("data/access.json"), true);
    $key =  search($json, 'user', $user)[0];

    // if (password_verify($pass, $key['password']))
    if ($key['password'] == $pass && $pass != '') {
        $_SESSION["role"] = serialize($key['role']);
        return true;
    }
    return false;
}

function search($array, $key, $value) {
    $results = array();
    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }
        foreach ($array as $subArray) {
            $results = array_merge($results, search($subArray, $key, $value));
        }
    }
    return $results;
}

function endsWith ($string, $test) {
    $strLen = strlen($string);
    $testLen = strlen($test);
    if ($testLen > $strLen) return false;
    return substr_compare($string, $test, $strLen - $testLen, $testLen) === 0;
}

function renameFiles($files) {
    print_r($files);
    foreach ($files as $file) {
        print $file;
        $newName = str_replace(Array('.html', '.htm'), '.php', $file);
        print $newName;
        rename('../' . $file, '../' . $newName);
    }
}