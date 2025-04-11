<?php
require('autoload.php');
use Misc\Path;
session_destroy();
$suffix = '';
if (isset($_REQUEST['error_message'])) {
    $suffix = '?'.http_build_query([
        'error_message' =>  $_REQUEST['error_message'],
    ]);
}
$redir_path = Path::combine($config['root_path'],$suffix);
//header("Location: {$config['root_path']}/{$suffix}");
header("Location: {$redir_path}");
die();