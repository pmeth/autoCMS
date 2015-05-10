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

function changePassword($password) {
    if (!file_exists("data/autocms-access.json")) return false;
    $json = json_decode(file_get_contents("data/autocms-access.json"), true);

    $_SESSION["password"] = $password;

    foreach($json as $key => $user) {
        if ($user['user'] == $_SESSION["user"]) {
            $json[$key]['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    addToLog('has changed', 'his/her password', null);

    $fp = fopen('data/autocms-access.json', 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
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
    if (!file_exists ($_SERVER['DOCUMENT_ROOT'] . '/admin/originals/')) mkdir($_SERVER['DOCUMENT_ROOT'] . '/admin/originals/', 0755);
    foreach ($files as $file) {
        copy('../' . $file, './originals/' . $file);
        $newName = str_replace(Array('.html', '.htm'), '.php', $file);
        rename('../' . $file, '../' . $newName);
    }
}

function getPageList() {
    if (!file_exists("data/autocms-pages.json")) return [];
    $json = json_decode(file_get_contents("data/autocms-pages.json"), true);
    return $json;
}

function hasNav() {
    if (!file_exists("data/autocms-nav.json")) return false;
    return true;
}

function hasBlog() {
    if (!file_exists("data/autocms-blog.json")) return false;
    return true;
}

function footerExists() {
    if (file_exists("data/autocms-footer.json")) return true;

    return false;
}

function buildFooterDataFile($files) {
    if (!footerExists()) {
        $footerArr = Array();
        $footerFound = false;

        foreach ($files as $file) {
            $fileData = file_get_contents('../' . $file, true);
            $dataFile = 'autocms-footer.json';

            $html = str_get_html($fileData);

            if (!$footerFound) {
                foreach ($html->find('.auto-footer .auto-edit, .auto-footer .auto-edit-text, .auto-footer .auto-edit-img, .auto-footer .auto-edit-bg-img') as $edit) {
                    $footerFound = true;
                    $fieldID = uniqid();
                    $desc = '';

                    if (strpos($edit->class, 'auto-edit-img') !== false) {
                        makeImageBGImage($edit, $footerArr, $dataFile, $fieldID, $desc);
                    } else if (strpos($edit->class, 'auto-edit-bg-img') !== false) {
                        makeImageBGImage($edit, $footerArr, $dataFile, $fieldID, $desc, true);
                    } else if (strpos($edit->class, 'auto-edit-text') !== false) {
                        makeHTMLText($edit, $footerArr, $dataFile, $fieldID, $desc, 'text');
                    } else if (strpos($edit->class, 'auto-edit') !== false) {
                        makeHTMLText($edit, $footerArr, $dataFile, $fieldID, $desc);
                    }
                }

                $footerHTML = '';
                foreach ($html->find('.auto-footer') as $edit) {
                    $edit->class = str_replace('auto-footer', '', $edit->class);
                    $footerHTML = clone $edit;
                    $edit->outertext = '<?php require_once("admin/other/autocms-footer.php") ?>';
                }

                $fp = fopen('other/autocms-footer.php', 'w');
                fwrite($fp, $footerHTML);
                fclose($fp);

            } else {
                foreach ($html->find('.auto-footer') as $edit) {
                    $edit->outertext = '<?php require_once("admin/other/autocms-footer.php") ?>';
                }
            }

            $fp = fopen('../' . $file, 'w');
            fwrite($fp, $html);
            fclose($fp);
        }

        $fp = fopen('data/autocms-footer.json', 'w');
        fwrite($fp, json_encode($footerArr));
        fclose($fp);
    }
}

function makeHTMLText(&$edit, &$dataArr, $dataFile, $fieldID, $desc, $type = 'html', $count = null, $repeatFieldID = null) {
    if (isset($edit->autocms)) $desc = $edit->autocms;

    if (is_null($repeatFieldID)) {
        $dataArr[$fieldID] = Array($type => trim($edit->innertext), 'description' => $desc, 'type' => $type);
        $edit->innertext = "<?=get('$dataFile', '$fieldID')?>";
    } else {
        $dataArr[$fieldID]['repeat'][$count][$repeatFieldID] = Array($type => trim($edit->innertext), 'description' => $desc, 'type' => $type);
        $edit->innertext = "<?=get('$dataFile', '$fieldID', " . '$x' . ", '$repeatFieldID')?>";
    }
    if ($type == 'html') {
        $edit->class = str_replace('auto-edit', '', $edit->class);
    } else {
        $edit->class = str_replace('auto-edit-text', '', $edit->class);
    }
    $edit->autocms = null;
}

function makeImageBGImage(&$edit, &$dataArr, $dataFile, $fieldID, $desc, $isBG = false, $count = null, $repeatFieldID = null) {
    if (isset($edit->autocms)) $desc = $edit->autocms;
    $tag = null;
    $source = null;

    if ($isBG) {
        $source = $edit->style;
        preg_match('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', $source, $matches);
        $source = $matches[4];
        $tag = $matches[0];
        if (substr($edit->src, 0, 1) == "/") $source = $_SERVER['DOCUMENT_ROOT'] . $edit->src;
    } else {
        $source = $edit->src;
        if (substr($edit->src, 0, 1) == "/") $source = $_SERVER['DOCUMENT_ROOT'] . $edit->src;
    }

    $fileExt = pathinfo(parse_url($edit->src, PHP_URL_PATH), PATHINFO_EXTENSION);
    $fileExt = getImageType($fileExt, $source);

    if ($fileExt != 'error') {
        $imgFileName = makeDateFolders() . uniqid() . '.' . $fileExt;

        copy($source, $_SERVER['DOCUMENT_ROOT'] . $imgFileName);

        if (!is_null($repeatFieldID)) {
            $dataArr[$fieldID]['repeat'][$count][$repeatFieldID] = Array('image' => $imgFileName, 'description' => $desc, 'type' => 'image');
        } else {
            $dataArr[$fieldID] = Array('image' => $imgFileName, 'description' => $desc, 'type' => 'image');
        }

        if ($isBG) {
            if (!is_null($repeatFieldID)) {
                $edit->style = str_replace($tag, '', $edit->style) . "background-image: url('<?=get('$dataFile', '$fieldID', ".'$x'.", '$repeatFieldID')?>');";
            } else {
                $edit->style = str_replace($tag, '', $edit->style) . "background-image: url('<?=get('$dataFile', '$fieldID')?>');";
            }
            $edit->class = str_replace('auto-edit-bg-img', '', $edit->class);
        } else {
            if (!is_null($repeatFieldID)) {
                $edit->src = "<?=get('$dataFile', '$fieldID', ".'$x'.", '$repeatFieldID')?>";
            } else {
                $edit->src = "<?=get('$dataFile', '$fieldID')?>";
            }
            $altText = $edit->alt;
            $altFieldID = uniqid();
            $dataArr[$altFieldID] = Array('alt' => $altText, 'description' => 'image alt text', 'type' => 'text', 'parent' => $fieldID);

            if (!is_null($repeatFieldID)) {
                $edit->alt = "<?=get('$dataFile', '$fieldID', ".'$x'.", '$altFieldID')?>";
            } else {
                $edit->alt = "<?=get('$dataFile', '$altFieldID')?>";
            }
            $edit->class = str_replace('auto-edit-img', '', $edit->class);
        }
        $edit->autocms = null;
    }
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
        $dataFile = 'page-' . str_replace(Array('.html', '.htm'), '.json', $file);
        $data = Array();

        // start collecting fields to add to data
        $fileData = file_get_contents('../' . $file, true);

        $html = str_get_html($fileData);

        foreach($html->find('.auto-head title') as $pageTitle) {
            $data['title'] = Array('text' => $pageTitle->innertext, 'description' => 'title', 'type' => 'text');
            $pageTitle->innertext = "<?=get('$dataFile', 'title')?>";
        }

        foreach($html->find('.auto-head meta') as $pageMeta) {
            if ($pageMeta->name == 'keywords' || $pageMeta->name == 'description' || $pageMeta->name == 'author') {
                $data[$pageMeta->name] = Array('text' => $pageMeta->content, 'description' => $pageMeta->name, 'type' => 'text');
                $pageMeta->content = "<?=get('$dataFile', '$pageMeta->name')?>";
            }
        }

        foreach($html->find('.auto-edit, .auto-edit-text, .auto-edit-img, .auto-edit-bg-img, .auto-repeat') as $edit) {
            $fieldID = uniqid();
            $desc = '';
            if (strpos($edit->class, 'auto-repeat') !== false) {

                if (isset($edit->autocms)) $desc = $edit->autocms;
                $data[$fieldID] = Array('repeat' => Array(), 'description' => $desc, 'type' => 'repeat');
                $count = 0;
                $data[$fieldID]['repeat'][$count] = Array();

                foreach($html->find('.auto-repeat .auto-edit, .auto-repeat .auto-edit-text, .auto-repeat .auto-edit-img, .auto-repeat .auto-edit-bg-img') as $repeat) {
                    $desc = '';

                    $repeatFieldID = uniqid();
                    if (strpos($repeat->class, 'auto-edit-img') !== false) {
                        makeImageBGImage($repeat, $data, $dataFile, $fieldID, $desc, false, $count, $repeatFieldID);
                    } else if (strpos($repeat->class, 'auto-edit-bg-img') !== false) {
                        makeImageBGImage($repeat, $data, $dataFile, $fieldID, $desc, true, $count, $repeatFieldID);
                    } else if (strpos($repeat->class, 'auto-edit-text') !== false) {
                        makeHTMLText($repeat, $data, $dataFile, $fieldID, $desc, 'text', $count, $repeatFieldID);
                    } else if (strpos($repeat->class, 'auto-edit') !== false) {
                        makeHTMLText($repeat, $data, $dataFile, $fieldID, $desc, 'html', $count, $repeatFieldID);
                    }
                }

                $edit->class = str_replace('auto-repeat', '', $edit->class);
                $edit->autocms = null;
                $edit->outertext = '<?php for ($x = 0; $x ' . "< repeatCount('$dataFile', '$fieldID');" . ' $x++) { ?>' . $edit->outertext . "<?php } ?>";

            } else if (strpos($edit->class, 'auto-edit-img') !== false) {
                makeImageBGImage($edit, $data, $dataFile, $fieldID, $desc);
            } else if (strpos($edit->class, 'auto-edit-bg-img') !== false) {
                makeImageBGImage($edit, $data, $dataFile, $fieldID, $desc, true);
            } else if (strpos($edit->class, 'auto-edit-text') !== false) {
                makeHTMLText($edit, $data, $dataFile, $fieldID, $desc, 'text');
            } else if (strpos($edit->class, 'auto-edit') !== false) {
                makeHTMLText($edit, $data, $dataFile, $fieldID, $desc);
            }
        }

        // write data file
        $fp = fopen('data/' . $dataFile, 'w');
        fwrite($fp, json_encode($data));
        fclose($fp);

        $fileTopper = '<?php require_once("admin/other/get.php") ?>';

        // write html file
        $fp = fopen('../' . $file, 'w');
        fwrite($fp, $fileTopper . $html);
        fclose($fp);
    }

    $fp = fopen('data/autocms-pages.json', 'w');
    fwrite($fp, json_encode($pageArr));
    fclose($fp);
}

function getImageType($fileExt, $source) {
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
    return $fileExt;
}

function getPageData($file) {
    $dataFile = 'data/page-' . $file . '.json';
    $json = json_decode(file_get_contents($dataFile), true);

    return $json;
}

function getRepeatData($file, $key) {
    $dataFile = 'data/page-' . $file . '.json';
    $json = json_decode(file_get_contents($dataFile), true);

    return $json[$key]['repeat'];
}

function getNavData() {
    $dataFile = 'data/autocms-nav.json';
    $json = json_decode(file_get_contents($dataFile), true);

    return $json;
}

function getFooterData() {
    $dataFile = 'data/autocms-footer.json';
    $json = json_decode(file_get_contents($dataFile), true);

    return $json;
}

function updatePage($file, $data) {
    $dataFile = 'data/page-' . $file . '.json';
    $json = json_decode(file_get_contents($dataFile), true);
    $changeLog = Array();

    foreach ($data as $key => $datum) {
        if ($key != 'key' && isset($json[$key]) && $json[$key][$json[$key]['type']] != trim($datum)) {
            $changeLog[] = Array('key' => $key, 'change' => Array('original' => $json[$key][$json[$key]['type']], 'new' => trim($datum)));
            $json[$key][$json[$key]['type']] = trim($datum);
        } else {
            list($repeatKey, $iteration, $itemKey) = explode("-", $key);
            if (isset($json[$repeatKey]['repeat'][$iteration][$itemKey]) && $json[$repeatKey]['repeat'][$iteration][$itemKey][$json[$repeatKey]['repeat'][$iteration][$itemKey]['type']] != trim($datum)) {
                $changeLog[] = Array('key' => $key, 'change' => Array('original' => $json[$repeatKey]['repeat'][$iteration][$itemKey][$json[$repeatKey]['repeat'][$iteration][$itemKey]['type']], 'new' => trim($datum)));
                $json[$repeatKey]['repeat'][$iteration][$itemKey][$json[$repeatKey]['repeat'][$iteration][$itemKey]['type']] = trim($datum);
            }
        }
    }

    if (count($changeLog) > 0) addToLog('has updated', $file . ' page', $changeLog);

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
}

function updateNav($data) {
    $dataFile = 'data/autocms-nav.json';
    $json = json_decode(file_get_contents($dataFile), true);
    $changeLog = Array();

    foreach ($data as $key => $datum) {
        if ($json[$key][$json[$key]['type']] != trim($datum)) {
            $changeLog[] = Array('key' => $key, 'change' => Array('original' => $json[$key][$json[$key]['type']], 'new' => trim($datum)));
            $json[$key][$json[$key]['type']] = trim($datum);
        }
    }

    if (count($changeLog) > 0) addToLog('has updated', 'navigation links', $changeLog);

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
}

function updateFooter($data) {
    $dataFile = 'data/autocms-footer.json';
    $json = json_decode(file_get_contents($dataFile), true);
    $changeLog = Array();

    foreach ($data as $key => $datum) {
        if ($json[$key][$json[$key]['type']] != trim($datum)) {
            $changeLog[] = Array('key' => $key, 'change' => Array('original' => $json[$key][$json[$key]['type']], 'new' => trim($datum)));
            $json[$key][$json[$key]['type']] = trim($datum);
        }
    }

    if (count($changeLog) > 0) addToLog('has updated', 'footer', $changeLog);

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
}

function saveDescription($file, $editKey, $editDesc) {
    $dataFile = 'data/' . $file . '.json';
    $json = json_decode(file_get_contents($dataFile), true);

    if (strpos($editKey, '-') !== false) {
        list($repeatKey, $iteration, $itemKey) = explode("-", $editKey);
        if (isset($json[$repeatKey]['repeat'][$iteration][$itemKey])) {
            $json[$repeatKey]['repeat'][$iteration][$itemKey]['description'] = trim($editDesc);
        }
    } else if (isset($json[$editKey])) {
        $json[$editKey]['description'] = $editDesc;
    }

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
}

function getAllNavigationData($files) {
    $dataFile = 'autocms-nav.json';

    if (!file_exists($dataFile)) {
        $navArr = Array();
    } else {
        $navArr = json_decode(file_get_contents('data/' . $dataFile), true);
    }

    foreach ($files as $file) {
        $fileData = file_get_contents('../' . $file, true);

        $html = str_get_html($fileData);

        foreach($html->find('.auto-nav') as $navigation) {
            if (isset($navigation->autocms)) {
                $desc = preg_replace("/[^a-z^A-Z^0-9_-]/", "", $navigation->autocms);

                $navArr[$desc] = Array('text' => $navigation->innertext, 'description' => $navigation->autocms, 'type' => 'text');
                $navigation->innertext = "<?=get('$dataFile', '$desc')?>";
                $navigation->href = str_replace(Array('index.html', 'index.htm', '.html', '.htm'), '/', '/' . $navigation->href);
                $navigation->href = str_replace('//', '/', $navigation->href);
            }
        }

        // write html file
        $fp = fopen('../' . $file, 'w');
        fwrite($fp, $html);
        fclose($fp);
    }

    $fp = fopen('data/' . $dataFile, 'w');
    fwrite($fp, json_encode($navArr));
    fclose($fp);
}

function copyApacheConfig() {
    if (file_exists('./other/.htaccess')) copy('./other/.htaccess', '../.htaccess');
}

function uploadFiles($page) {
    foreach ($_FILES as $key => $data) {
        if ($_FILES[$key]['error'] == 0) {
            $sourceName = $_FILES[$key]['name'];

            $fileExt = pathinfo($sourceName, PATHINFO_EXTENSION);
            if ($fileExt === '') {
                $detect = exif_imagetype($sourceName);
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
                $imgFileName = makeDateFolders() . uniqid() . '.' . $fileExt;
                move_uploaded_file($_FILES[$key]['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $imgFileName);

                $dataFile = 'data/page-' . $page . '.json';
                $json = json_decode(file_get_contents($dataFile), true);

                foreach ($json as $jsonKey => $datum) {
                    if ($key == $jsonKey && $json[$key]['type'] == 'image' && isset($json[$key])) {
                        $json[$key]['image'] = $imgFileName;
                    } else {
                        list($repeatKey, $iteration, $itemKey) = explode("-", $key);
                        if (isset($json[$repeatKey]['repeat'][$iteration][$itemKey]) && $json[$repeatKey]['repeat'][$iteration][$itemKey]['type'] == 'image') {
                            $json[$repeatKey]['repeat'][$iteration][$itemKey]['image'] = $imgFileName;
                        }
                    }
                }

                $fp = fopen($dataFile, 'w');
                fwrite($fp, json_encode($json));
                fclose($fp);
            }
        }
    }
}

function duplicateRepeat($page, $key, $num) {
    $dataFile = 'data/page-' . $page . '.json';
    $json = json_decode(file_get_contents($dataFile), true);

    foreach ($json as $jsonKey => $datum) {
        if ($key != 'key' && $jsonKey == $key && isset($json[$key]) && $json[$key]['type'] == 'repeat' && count($json[$key]['repeat']) > $num && isset($json[$key]['repeat'][$num])) {
            $json[$key]['repeat'][] = $json[$key]['repeat'][$num];
        }
    }

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
}

function deleteRepeat($page, $key, $num) {
    $dataFile = 'data/page-' . $page . '.json';
    $json = json_decode(file_get_contents($dataFile), true);

    foreach ($json as $jsonKey => $datum) {
        if ($key != 'key' && $jsonKey == $key && isset($json[$key]) && $json[$key]['type'] == 'repeat' && count($json[$key]['repeat']) > $num && isset($json[$key]['repeat'][$num])) {
            array_splice($json[$key]['repeat'], $num, 1);
        }
    }

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($json));
    fclose($fp);
}

function addToLog($action, $page, $details = null) {
    $dataFile = 'data/autocms-log.json';

    if (!file_exists($dataFile)) {
        $logArr = Array();
    } else {
        $logArr = json_decode(file_get_contents($dataFile), true);
    }

    $logArr[] = Array('user' => $_SESSION["user"], 'action' => $action, 'page' => $page, 'timestamp' => time(), 'details' => $details);

    if (count($logArr) > _LOG_COUNT_MAX_) {
        $logArr = array_slice($logArr, -_LOG_COUNT_MAX_);
    }

    $fp = fopen($dataFile, 'w');
    fwrite($fp, json_encode($logArr));
    fclose($fp);
}

function getLogData($num = 0, $get = null, $user = null) {
    $dataFile = 'data/autocms-log.json';
    if (file_exists($dataFile)) {
        $json = json_decode(file_get_contents($dataFile), true);

        if (!is_null($user)) {
            $userArr = Array();
            foreach ($json as $key => $data) {
                if ($json[$key]['user'] == $user) $userArr[] = $json[$key];
            }

            return array_reverse($userArr);
        }

        return array_reverse(array_slice($json, $num, $get));
    } else {
        return Array();
    }
}

function makeDateFolders() {
    $year = date("Y", time());
    if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/admin/images/'.$year.'/')) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . '/admin/images/'.$year.'/');
    }
    $month = date("m", time());
    if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/admin/images/'.$year.'/'.$month.'/')) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . '/admin/images/'.$year.'/'.$month.'/');
    }

    return '/admin/images/'.$year.'/'.$month.'/';
}

function processBlog($files) {
    /*
    $dataFile = 'data/autocms-blog.json';
    $blogFolder = $_SERVER['DOCUMENT_ROOT'] . '/admin/data/blog/';
    if (!is_dir($blogFolder)) {
        mkdir($blogFolder);
    }

    $blogArr = Array();

    foreach ($files as $file) {
        $fileData = file_get_contents('../' . $file, true);

        $html = str_get_html($fileData);

        foreach($html->find('.auto-blog-list, .auto-blog-head, .auto-blog-post, .auto-blog-title, .auto-blog-bg-img, .auto-blog-img, .auto-blog-short, .auto-blog-link, .auto-blog-full') as $navigation) {

        }

        // write html file
        $fp = fopen('../' . $file, 'w');
        fwrite($fp, $html);
        fclose($fp);
    }

    $fp = fopen('data/' . $dataFile, 'w');
    fwrite($fp, json_encode($blogArr));
    fclose($fp);
    */
}