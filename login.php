<?php
require_once('autoload.php');
if (isset($_REQUEST['redirect_url'])) {
    $_SESSION['redirect_url_once'] = $_REQUEST['redirect_url'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (substr($config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Login to Neverer Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <!--<script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="js/login-google.js" async defer></script>
    <meta name="google-signin-client_id" content="<?= @$config['GOOGLE_CLIENTID'] ?>">-->
</head>
<body>    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <div class="row text-center">
        <div class="col-12">
            <h1>Login page</h1>
        </div>
    </div>
    <?php
if(isset($_REQUEST['error'])):
    ?>
    <div class="row text-center">
        <div class="col-12">
            <div class="alert alert-warning"><?php echo $_REQUEST['error']; ?></div>
        </div>
    </div>
    <?php
endif;
    $auth_methods = AuthMethod::getAll();
    foreach ($auth_methods as $auth_method) {
        if ($auth_method->handler == '') {
            // Special case - HTML render only
            echo "<div class='row text-center'>\n";
            echo $auth_method->image;
            echo "</div>\n";
        } else {
            // Render picture and link
            echo "<div class='row text-center'>\n";
            echo "<div class='col-12'><a class='btn btn-lg' href='{$auth_method->handler}'><img src='{$auth_method->image}' height='60' /></a></div>\n";
            echo "</div>\n";
        }
    }
    ?>
</body>
</html>