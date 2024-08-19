<?php
require_once('autoload.php');
$discard = new User(); // To force autoloading of User class
// For clarity, $user contains the user object and $_SESSION['USER'] contains the serialized version
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_SESSION['USER'])) {
        $user = unserialize($_SESSION['USER']);
        $_SESSION['USER'] = serialize($user);
    }
}

if(isset($_GET['params'])) {
    $page_parts = explode('/', $_GET['params']);
} else {
    $page_parts = [];
}
$params = [];

// Parse URL into a page with optional params
if (count($page_parts)==0 || (empty($page_parts[0]))) {
    // If no params, treat as crossword index
    $stub = 'crossword_index';
} elseif (count($page_parts)==1) {
    // If only one URL part, treat as /[url-part]/index
    $page_parts[] = 'index';
    $stub = "{$page_parts[0]}_{$page_parts[1]}";
} elseif (count($page_parts)>=2) {
    // Move extraneous page parts to params 
    while (count($page_parts)>2) {
        array_unshift($params, (array_pop($page_parts)) );
    }
    $stub = "{$page_parts[0]}_{$page_parts[1]}";
}

// Get information about the page we're serving
$pageinfo = PageInfo::get($stub);

// Check if we need to authenticate now
if ($pageinfo->authSetting === PageInfo::AUTH_EARLY) {
    $pageinfo->processRequestData();
    User::loginCheck($pageinfo->redirectOnFail);
}


$page = "pages/{$stub}.php";

ob_start(); // Required, as we're including login-check further down

if (!isset($_SESSION['PAGE_LOADCOUNTS'])) {
    // This keeps track of how many times the user has seen each page that wants to know
    $_SESSION['PAGE_LOADCOUNTS'] = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    if (strpos(strtolower($_SERVER['SERVER_NAME']), 'localhost') === false):
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-K2LEKFKTGT"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-K2LEKFKTGT');
    </script>
    <!-- END GOOGLE -->
    <?php
    else:
    echo "<!-- NO ANALYTICS ON LOCALHOST -->\n";
    endif;
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (substr($config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Neverer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="<?= $config['root_path'] ?>/css/app.css" rel="stylesheet">
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>

<?php

if (!@include_once('inc/header.php')) {
    if (!@include_once('../inc/header.php')) {
        if (!@include_once('../../inc/header.php')) {
            require_once('../../../inc/header.php');
        }
    }
}

if (!@include_once($page)) {
    http_response_code(404);
    echo "<h1>You’ve Lost That Lovin’ Feelin’...</h1>\n";
    echo "<h2>Or, more accurately, you've clicked a wrong link.</h2>\n";
    echo "<p>Look, it's most likely our fault - sorry. Or you've mistyped something. Who knows?</p>\n";
    echo "<p class='text-body-secondary'><small>{$page}</small></p>\n";
    ob_end_flush();
    die();
} else {
    if ($pageinfo->authSetting == PageInfo::AUTH_LATE) {
        require_once('inc/login_check.php');
    }
    ob_end_flush();
}

// Check if we need to authenticate now
if ($pageinfo->authSetting === PageInfo::AUTH_LATE) {
    $pageinfo->processRequestData();
    User::loginCheck($pageinfo->redirectOnFail);
}

if (isset($config['KOFI_SHOW']) && $config['KOFI_SHOW']):
?>
    <!-- KO-FI -->
    <script src='https://storage.ko-fi.com/cdn/scripts/overlay-widget.js'></script>
    <script>
    kofiWidgetOverlay.draw('al79372', {
        'type': 'floating-chat',
        'floating-chat.donateButton.text': 'Support me',
        'floating-chat.donateButton.background-color': '#0dcaf0',
        'floating-chat.donateButton.text-color': '#fff'
    });
    </script>
    <!-- END KO-FI -->
<?php
endif;
?>
</body>
</html>