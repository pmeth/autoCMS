<?php

class FooterData extends DataBuild {
    public $dataFile = 'autocms-footer.json';
    public $sectionName = 'footer';

    public function hasPHPFile() {
        return file_exists($_SERVER['DOCUMENT_ROOT'] . 'admin/other/autocms-footer.php');
    }

    public function buildDataFile($files) {
        if (!$this->hasPHPFile()) {
            $footerFound = false;
        } else {
            $footerFound = true;
        }

        foreach ($files as $file) {
            $fileData = file_get_contents('../' . $file, true);
            $html = str_get_html($fileData);

            if (!$footerFound) {
                foreach ($html->find('.auto-footer .auto-color, .auto-footer .auto-edit, .auto-footer .auto-edit-text, .auto-footer .auto-link, .auto-footer .auto-edit-img, .auto-footer .auto-edit-bg-img') as $edit) {
                    $footerFound = true;
                    $fieldID = uniqid();
                    $desc = '';

                    if (strpos($edit->class, 'auto-edit-img') !== false) {
                        $this->makeImageBGImage($edit, $this->data, $this->dataFile, $fieldID, $desc);
                    } else if (strpos($edit->class, 'auto-edit-bg-img') !== false) {
                        $this->makeImageBGImage($edit, $this->data, $this->dataFile, $fieldID, $desc, true);
                    } else if (strpos($edit->class, 'auto-link') !== false) {
                        $this->makeLink($edit, $this->data, $this->dataFile, $fieldID, $desc);
                    } else if (strpos($edit->class, 'auto-edit-text') !== false) {
                        $this->makeHTMLText($edit, $this->data, $this->dataFile, $fieldID, $desc, 'text');
                    } else if (strpos($edit->class, 'auto-edit') !== false) {
                        $this->makeHTMLText($edit, $this->data, $this->dataFile, $fieldID, $desc);
                    } else if (strpos($edit->class, 'auto-color') !== false) {
                        $this->makeColor($edit, $this->data, $this->dataFile, $fieldID, $desc);
                    }
                }

                $footerHTML = '';
                foreach ($html->find('.auto-footer') as $edit) {
                    $edit->class = str_replace('auto-footer', '', $edit->class);
                    if (trim($edit->class) === '') $edit->class = null;
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
    }
}

class Footer {
    function get() {
        $users = new UsersData();
        if ($users->checkPass() && !$users->authNeeded()) {
            include_once('admin-pages/edit-footer.php');
        } else {
            include_once('401.html');
        }
    }
    function post() {
        $users = new UsersData();
        if ($users->checkPass() && !$users->authNeeded()) {

            $footer = new FooterData();
            $footer->updateData($_POST);

            header('Location: /admin/footer/?updated=true');
        } else {
            include_once('401.html');
        }
    }
}
