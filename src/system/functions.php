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
    if (!file_exists("data/autocms-access.json")) return true;
    $json = json_decode(file_get_contents("data/autocms-access.json"), true);
    return sizeof($json) === 0;
}

function checkPass($user = null, $pass = null) {
    if (!file_exists("data/autocms-access.json")) return false;

    if (is_null($user)) $user = $_SESSION["user"];
    if (is_null($pass)) $pass = $_SESSION["password"];
    $json = json_decode(file_get_contents("data/autocms-access.json"), true);
    $key =  search($json, 'user', $user)[0];

    if (password_verify($pass, $key['password'])) {
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

function endsWith($string, $test) {
    $strLen = strlen($string);
    $testLen = strlen($test);
    if ($testLen > $strLen) return false;
    return substr_compare($string, $test, $strLen - $testLen, $testLen) === 0;
}

function renameFiles($files) {
    foreach ($files as $file) {
        $newName = str_replace(Array('.html', '.htm'), '.php', $file);
        rename('../' . $file, '../' . $newName);
    }
}

function getPageList() {
    if (!file_exists("data/autocms-pages.json")) return [];
    $json = json_decode(file_get_contents("data/autocms-pages.json"), true);
    return $json;
}

function buildDataFilesByTags($files) {
    if (!file_exists("data/autocms-pages.json")) {
        $pageArr = Array();
    } else {
        $pageArr = json_decode(file_get_contents("data/autocms-pages.json"), true);
    }

    foreach ($files as $file) {
        $pageArr[] = str_replace(Array('.html', '.htm'), '', $file);

        // create datafile to store stuff
        $dataFile = str_replace(Array('.html', '.htm'), '.json', $file);
        $data = Array();

        // start collecting fields to add to data
        $fileData = file_get_contents('../' . $file, true);

        $html = str_get_html($fileData);

        foreach($html->find('title') as $pageTitle) {
            $data['title'] = Array('text' => $pageTitle->innertext, 'description' => 'title', 'type' => 'text');
            $pageTitle->innertext = "<?=get('$dataFile', 'title')?>";
        }

        foreach($html->find('meta') as $pageMeta) {
            if ($pageMeta->name == 'keywords' || $pageMeta->name == 'description' || $pageMeta->name == 'author') {
                $data[$pageMeta->name] = Array('text' => $pageMeta->content, 'description' => $pageMeta->name, 'type' => 'text');
                $pageMeta->content = "<?=get('$dataFile', '$pageMeta->name')?>";
            }
        }

        foreach($html->find('.auto-edit, .auto-edit-img, .auto-edit-bg-img') as $edit) {
            $fieldID = uniqid();
            $desc = '';
            if (strpos($edit->class, 'auto-edit-img') !== false) {
                if (isset($edit->autocms)) $desc = $edit->autocms;

                $source = $edit->src;
                if (substr($edit->src, 0, 1) == "/") $source = $_SERVER['DOCUMENT_ROOT'] . $edit->src;

                $fileExt = pathinfo(parse_url($edit->src,PHP_URL_PATH),PATHINFO_EXTENSION);

                if ($fileExt === '') {
                    $detect = exif_imagetype($source);
                    if ($detect == IMAGETYPE_GIF) {
                        $fileExt = 'gif';
                    } else if ($detect == IMAGETYPE_JPEG) {
                        $fileExt = 'jpg';
                    } else if ($detect == IMAGETYPE_PNG) {
                        $fileExt = 'jpg';
                    } else {
                        $fileExt = 'error';
                    }
                }

                if ($fileExt != 'error') {
                    $imgFileName = '/admin/images/' . uniqid() . '.' . $fileExt;

                    copy($source, $_SERVER['DOCUMENT_ROOT'] . $imgFileName);

                    $data[$fieldID] = Array('image' => $imgFileName, 'description' => $desc, 'type' => 'image');
                    $edit->src = "<?=get('$dataFile', '$fieldID')?>";
                }
            } else if (strpos($edit->class, 'auto-edit-bg-img') !== false) {

            } else if (strpos($edit->class, 'auto-edit') !== false) {
                if (isset($edit->autocms)) $desc = $edit->autocms;
                $data[$fieldID] = Array('html' => $edit->innertext, 'description' => $desc, 'type' => 'html');
                $edit->innertext = "<?=get('$dataFile', '$fieldID')?>";
            }
        }

        // write data file
        $fp = fopen('data/page-' . $dataFile, 'w');
        fwrite($fp, json_encode($data));
        fclose($fp);

        $fileTopper = '<?php require_once("admin/system/get.php") ?>';

        // write html file
        $fp = fopen('../' . $file, 'w');
        fwrite($fp, $fileTopper . $html);
        fclose($fp);
    }

    $fp = fopen('data/autocms-pages.json', 'w');
    fwrite($fp, json_encode($pageArr));
    fclose($fp);
}

function getPageData($file) {
    $dataFile = 'data/page-' . $file . '.json';
    $json = json_decode(file_get_contents($dataFile), true);

    return $json;
}

function updatePage($file, $data) {
    $dataFile = 'data/page-' . $file . '.json';
    $json = json_decode(file_get_contents($dataFile), true);

    foreach ($data as $key => $datum) {
        $json[$key][$json[$key]['type']] = trim($datum);
    }

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
}