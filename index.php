<?php

require_once('autoload.php');

use Logging\LoggedError;
use Security\User, Security\PageInfo;

// Do some resolving of requests relative to root before any other routing
$root_marker = '~ROOT~';
$root_pos = strpos($_SERVER['REQUEST_URI'],$root_marker);
if ($root_pos !== false) {
    $redirect = $config['root_path'] . substr($_SERVER['REQUEST_URI'], $root_pos + strlen($root_marker));
    $extension = null;
    if (strpos($redirect,'.') !== false) {
        $parts = explode('.',$redirect);
        $extension = array_pop($parts);
    }
    switch ($extension) {
        case 'map':
        case 'css':
        case 'js':
            // These can be served directly with no security issue
            //LoggedError::log(LoggedError::TYPE_DEBUG, 0, __FILE__, __LINE__, "Serving {$_SERVER['REQUEST_URI']} from {$redirect}");
            $relative_path = substr($_SERVER['REQUEST_URI'], $root_pos + strlen($root_marker) + 1);
            $file = @file_get_contents($relative_path);
            die($file);
            break;
        default:
            //LoggedError::log(LoggedError::TYPE_DEBUG, 0, __FILE__, __LINE__, "Redirecting {$_SERVER['REQUEST_URI']} to {$redirect}");
            header("Location: {$redirect}");
            break;
    }
}

User::ensureLoaded(); // To force autoloading of User class
// For clarity, $user contains the user object and $_SESSION['USER'] contains the serialized version
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_SESSION['USER'])) {
        try {
            $user = unserialize($_SESSION['USER']);
            $_SESSION['USER'] = serialize($user);
        } catch (\Exception $ex) {
            LoggedError::log(LoggedError::TYPE_PHP,1,__FILE__,__LINE__,"Invalid USER value in session: ".$ex->getMessage());
            unset($_SESSION['USER']);
        } catch (\Error $err) {
            LoggedError::log(LoggedError::TYPE_PHP,1,__FILE__,__LINE__,"Invalid USER value in session: ".$err->getMessage());
            unset($_SESSION['USER']);
        }
    }
}

if(isset($_GET['params'])) {
    $page_parts = explode('/', $_GET['params']);
} else {
    $page_parts = [];
}
$params = [];
//LoggedError::log(LoggedError::TYPE_PHP, 2, __FILE__, __LINE__, "");
//error_log($_SERVER['REQUEST_URI'].' ==> '.$_SERVER['QUERY_STRING']);

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
    // Asterisk means 'no subpage'
    if ($page_parts[1] == '*') {
        $stub = $page_parts[0];
    } else {
        $stub = "{$page_parts[0]}_{$page_parts[1]}";
    }
}

// Get information about the page we're serving
$pageinfo = PageInfo::get($stub);

// Check if we need to authenticate now
if ($pageinfo->authSetting === PageInfo::AUTH_EARLY) {
    $pageinfo->processRequestData();
    User::loginCheck($pageinfo->redirectOnFail);
}

$allowed_domains = ['pages','ajax'];
$domain = 'pages';
if (isset($_REQUEST['domain'])) {
    if (in_array($_REQUEST['domain'], $allowed_domains, true)) { $domain = $_REQUEST['domain']; }
}
$page = "{$domain}/{$stub}.php";

// Ajax calls get all the class loading etc above, but don't get any template output 
if ($domain == 'ajax') {
    //error_log("Serving ajax page {$page}");
    @include_once($page);
    die();
}

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

    function gtag() {
        dataLayer.push(arguments);
    }
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
    <title><?= (substr($config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Neverer
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="<?= $config['root_path'] ?>/css/app.css" rel="stylesheet">
    <script type='text/javascript'>
    // Variables from PHP script
    <?php
        echo "\t\tvar root_path = \"{$config['root_path']}\";\n";
        ?>
    // Stub for debug (stub functions so that code can call these without throwing exceptions)
    let debugPane = {};
    debugPane.print = function() {};
    debugPane.clear = function() {};
    </script>
</head>

<body>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous">
    </script>
    <?php
if (file_exists("js/{$stub}.js")) {
    echo "\t<script type='text/javascript' src='{$config['root_path']}/js/{$stub}.js'></script>\n";
}

if (!@include_once('inc/header.php')) {
    if (!@include_once('../inc/header.php')) {
        if (!@include_once('../../inc/header.php')) {
            require_once('../../../inc/header.php');
        }
    }
}

if (!@include_once($page)) {
    http_response_code(404);
    echo "<h1>We're stuck on this one...</h1>\n";
    echo "<h2>Or, more accurately, you seem to have clicked a wrong link.</h2>\n";
    echo "<p>Look, it's may well be our fault - sorry. Or you've mistyped something. Who knows?</p>\n";
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
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div id='status-bar'>&nbsp;</div>
        </div>
    </footer>
    <?php
if (Security\Config::getValueOrDefault('debug_pane',false)) {
    echo <<<END_HTML
<script type='text/javascript' src="{$config['root_path']}/js/debug.js"></script>
<footer class="footer mt-auto py-3 bg-light">
  <div class="container">
    <div id='debug-info'>&nbsp;</div>
  </div>
</footer>
END_HTML;
}
?>
</body>

</html>