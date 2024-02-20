<?php
require('autoload.php');
session_destroy();
$suffix = '';
if (isset($_REQUEST['error_message'])) {
    $suffix = '?'.http_build_query([
        'error_message' =>  $_REQUEST['error_message'],
    ]);
}
header("Location: {$config['root_path']}/{$suffix}");
die();