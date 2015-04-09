<?php
session_start();

define("VERSION", "0.1.0");

require_once('system/Toro.php');
require_once('system/statusreturn.php');
require_once('system/functions.php');
require_once('system/handlers.php');
require_once('system/simple_html_dom.php');

ToroHook::add("404", function() {
    echo json_encode(StatusReturn::E404(), JSON_NUMERIC_CHECK);
});

ToroHook::add("404Web", function() {
    include_once('admin-pages/404.html');
});

Toro::serve(array(
    '/admin/'                   => 'Init',
    '/login/'                   => 'Login',
    '/logout/'                  => 'Logout',
    '/dash/'                    => 'Dash',
    '/dash/:string'             => 'Dash',
    '/page/:alpha'              => 'Page'
));
