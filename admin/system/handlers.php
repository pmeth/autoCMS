<?php

class Init {
    function get() {
        if (authNeeded()) {
            include_once('admin-pages/init-setup.php');
        } else if (checkPass() && !authNeeded()) {
            include_once('admin-pages/init.php');
        } else {
            include_once('admin-pages/login.php');
        }
    }
    function post() {
        if (checkPass($_POST['user'], $_POST['password']) && !authNeeded()) {
            $_SESSION["user"] = $_POST['user'];
            $_SESSION["password"] = $_POST['password'];

            header('Location: /admin/');
        }
    }
}

class Login {
    function get() {
        include_once('admin-pages/login.php');
    }
    function post() {
        if (authNeeded()) {
            if ($_POST['user'] != '' && $_POST['password'] != '' && $_POST['password'] == $_POST['password2']) {

                $_SESSION["user"] = $_POST['user'];
                $_SESSION["password"] = $_POST['password'];

                $userArray = Array('user' => $_POST['user'], 'password' => password_hash($_POST['password'], PASSWORD_DEFAULT), 'role' => Array('admin'));

                $fp = fopen('data/autocms-access.json', 'w');
                fwrite($fp, '['.json_encode($userArray).']');
                fclose($fp);

                include_once('admin-pages/login.php');
            } else {
                // todo: better error messaging
                include_once('admin-pages/init-setup.php?error=error');
            }
        } else {
            include_once('admin-pages/404.html');
        }
    }
}

class Logout {
    function get() {

        $_SESSION["user"] = '';
        $_SESSION["password"] = '';
        $_SESSION["role"] = '';

        session_destroy();

        include_once('admin-pages/login.php');
    }
}

class Dash {
    function get() {
        if (checkPass() && !authNeeded()) {
            include_once('admin-pages/dash.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post($action = null) {
        if ($action == 'process' && checkPass() && !authNeeded()) {

            getAllNavigationData($_POST['files']);
            processBlog($_POST['files']);
            buildFooterDataFile($_POST['files']);

            buildDataFilesByTags($_POST['files']);
            renameFiles($_POST['files']);
            copyApacheConfig();
            createXMLSitemap();
            createAnalytics();
            addToLog('has initiated the CMS', 'on all pages');

            header('Location: /admin/');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post_xhr($action = null) {
        if (is_null($action)) {
            echo json_encode(StatusReturn::E400('400 Missing Required Data!'), JSON_NUMERIC_CHECK);
        } else if ($action == 'change-pass' && checkPass() && !authNeeded()) {
            if ($_POST['current'] != '' && $_POST['password'] != '' && $_POST['password'] == $_POST['password2'] && checkPass(null, $_POST['current'])) {

                changePassword($_POST['password']);

                echo json_encode(StatusReturn::S200('Password Changed!'), JSON_NUMERIC_CHECK);
            } else {
                echo json_encode(StatusReturn::E400('400 Missing Required Data!'), JSON_NUMERIC_CHECK);
            }
        } else {
            echo json_encode(StatusReturn::E401('401 Not Authorized!'), JSON_NUMERIC_CHECK);
        }
    }
}

class Settings {
    function get() {
        if (checkPass() && !authNeeded()) {
            include_once('admin-pages/settings.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post() {
        if (checkPass() && !authNeeded()) {
            // todo: update settings

            header('Location: /admin/settings/?updated=true');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Analytics {
    function get() {
        if (checkPass() && !authNeeded()) {
            include_once('admin-pages/analytics.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post() {
        if (checkPass() && !authNeeded()) {

            updateAnalytics($_POST);

            header('Location: /admin/analytics/?updated=true');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Blog {
    function get() {
        if (checkPass() && !authNeeded()) {
            include_once('admin-pages/blog.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class BlogPost {
    function get($post_id = null, $action = null) {
        if (is_null($post_id)) {
            include_once('admin-pages/404.html');
        } else if (checkPass() && !authNeeded()) {
            if ($action == 'publish') {
                publishPost($post_id);
                header('Location: /admin/blog/?updated=true');
                orderBlog();
                die();
            } else if ($action == 'unpublish') {
                unpublishPost($post_id);
                header('Location: /admin/blog/?updated=true');
                orderBlog();
                die();
            } else if ($action == 'trash') {
                trashPost($post_id);
                header('Location: /admin/blog/?updated=true');
                orderBlog();
                die();
            }

            if ($post_id == 'new') $post_id = uniqid();
            else $postInfo = getPostData($post_id);

            include_once('admin-pages/post.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post($post_id = null, $action = null) {
        if (is_null($post_id)) {
            include_once('admin-pages/404.html');
        } else if (checkPass() && !authNeeded()) {

            if ($action == 'update') {
                updateBlogPost($post_id, $_POST, isset($_POST['publish']));
                uploadFiles($post_id, true);
            }
            orderBlog();

            header('Location: /admin/blog/?updated=true');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Page {
    function get($page = null) {
        if (is_null($page) && checkPass() && !authNeeded()) {
            include_once('admin-pages/dash.php');
        } else if (checkPass() && !authNeeded()) {

            $data = getPageData($page);

            include_once('admin-pages/page.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post($page = null) {
        if (is_null($page)) {
            include_once('admin-pages/404.html');
        } else if (!is_null($page) && checkPass() && !authNeeded()) {

            updatePage($page, $_POST);
            uploadFiles($page);

            header('Location: /admin/page/' . $page . '/?updated=true');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Nav {
    function get() {
        if (checkPass() && !authNeeded()) {
            include_once('admin-pages/nav.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post() {
        if (checkPass() && !authNeeded()) {

            updateNav($_POST);

            header('Location: /admin/nav/?updated=true');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Logs {
    function get() {
        if (checkPass() && !authNeeded()) {
            include_once('admin-pages/logs.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Footer {
    function get() {
        if (checkPass() && !authNeeded()) {
            include_once('admin-pages/edit-footer.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post() {
        if (checkPass() && !authNeeded()) {

            updateFooter($_POST);

            header('Location: /admin/footer/?updated=true');
        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Description {
    function post_xhr($page = null) {
        if (is_null($page)) {
            echo json_encode(StatusReturn::E400('400 Missing Required Data!'), JSON_NUMERIC_CHECK);
        } else if ($page != 'nav' && checkPass() && !authNeeded()) {

            saveDescription('page-' . $page, $_POST['pk'], $_POST['value']);

            echo json_encode(StatusReturn::S200('Description Saved!'), JSON_NUMERIC_CHECK);
        } else if (checkPass() && !authNeeded()) {

            saveDescription('autocms-' . $page, $_POST['pk'], $_POST['value']);

            echo json_encode(StatusReturn::S200('Description Saved!'), JSON_NUMERIC_CHECK);
        } else {
            echo json_encode(StatusReturn::E401('401 Not Authorized!'), JSON_NUMERIC_CHECK);
        }
    }
}

class RepeatDel {
    function get($page, $key, $num) {
        if (is_null($page) || is_null($key) || is_null($num)) {
            include_once('admin-pages/404.html');
        } else if (checkPass() && !authNeeded()) {
            deleteRepeat($page, $key, $num);

            header('Location: /admin/page/' . $page . '/repeat/' . $key . '/');

        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class RepeatDup {
    function get($page, $key, $num) {
        if (is_null($page) || is_null($key) || is_null($num)) {
            include_once('admin-pages/404.html');
        } else if (checkPass() && !authNeeded()) {
            duplicateRepeat($page, $key, $num);

            header('Location: /admin/page/' . $page . '/repeat/' . $key . '/');

        } else {
            include_once('admin-pages/401.html');
        }
    }
}

class Repeat {
    function get($page = null, $key = null) {
        if (is_null($page) || is_null($key)) {
            include_once('admin-pages/dash.php');
        } else if (checkPass() && !authNeeded()) {

            $data = getRepeatData($page, $key);

            include_once('admin-pages/repeat.php');
        } else {
            include_once('admin-pages/401.html');
        }
    }
    function post($page = null, $key = null) {
        if (is_null($page) || is_null($key)) {
            include_once('admin-pages/404.html');
        } else if (!is_null($page) && checkPass() && !authNeeded()) {

            updatePage($page, $_POST);
            uploadFiles($page);

            header('Location: /admin/page/' . $page . '/repeat/' . $key . '/?updated=true');

        } else {
            include_once('admin-pages/401.html');
        }
    }
}
