<?php
    // Class & session loading
    require_once('autoload.php');
    $discard = new User(); // To force autoloading of User class
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (isset($_SESSION['USER'])) { $_SESSION['USER'] = unserialize(serialize($_SESSION['USER'])); }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (substr($config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Login to Neverer Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <script type='text/javascript'>
    </script>
</head>
<body>
<?php
    $display_errors = [];
    if (isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'signin':
                $user = User::findFirst(['identifier', '=', $_POST['username']]);
                if ($user === null) {
                    $display_errors[] = "Login credentials are not valid";
                } else {
                    if ($user->checkPassword($_POST['password'])) {
                        // Log them in
                    } else {
                        $display_errors[] = "Login credentials are not valid";
                    }
                }
                break;
            case 'signup':
                $user = User::findFirst(['identifier', '=', $_POST['newusername']]);
                if ($user !== null) {
                    $display_errors[] = "Sorry - that username is already taken";
                } else {
                    if ($_POST['newpassword'] !== $_POST['newpasswordrepeat']) { 
                        $display_errors[] = "Passwords do not match";
                    } else {
                        $user = new User();
                        $user->authmethod_id = AuthMethod::findFirst(['methodName', '=', 'neverer'])->id;
                        $user->identifier = $_POST['newusername'];
                        $user->display_name = $user->identifier;
                        $user->save();
                        $user->createPassword($_POST['newpassword']);
                    }
                }
                break;
            default:
                // Ignore
                error_log('Unexpected action '.$_POST['action']. ' passed to '.$_SERVER['PHP_SELF']);
                break;
        }
    }
?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <?php
    require_once('vendor/autoload.php');
    require_once('autoload.php');
    ?>
    <h3>Sign in</h3>
<?php
    if (count($display_errors)>0 && ($_POST['action']=='signin')) {
        foreach ($display_errors as $display_error) {
echo <<<END_HTML
<div class="alert alert-primary" role="alert">
    {$display_error}
</div>
END_HTML;
        }
    }
?>
    <form id='signin' method='POST' action=''>
        <div class='form-group'>
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" id="username" aria-describedby="usernameHelp" placeholder="Enter username">
            <small id="usernameHelp" class="form-text text-muted">The username you specified when registering. This may be your email address.</small>
        </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Password">
    </div>
    <br />
    <input type="hidden" id="action" name="action" value="signin">
    <button type="submit" class="btn btn-primary">Log in</button>
    </form>
    <br /><hr /><br />
    <h3>New to Neverer? Sign up for an account!</h3>
<?php
    if (count($display_errors)>0 && ($_POST['action']=='signup')) {
        foreach ($display_errors as $display_error) {
echo <<<END_HTML
<div class="alert alert-primary" role="alert">
    {$display_error}
</div>
END_HTML;
        }
    }
?>
    <form id='signup' method='POST' action=''>
        <div class='form-group'>
            <label for="newusername">Username</label>
            <input type="text" class="form-control" id="newusername" name="newusername" aria-describedby="newusernameHelp" placeholder="Enter username">
            <small id="newusernameHelp" class="form-text text-muted">The username you would like to use. Your email address may be a good idea, as it's unique to you.</small>
        </div>
    <div class="form-group">
        <label for="newpassword">Password</label>
        <input type="password" class="form-control" id="newpassword" name="newpassword" placeholder="Password">
    </div>
    <div class="form-group">
        <label for="newpasswordrepeat">Re-enter password</label>
        <input type="password" class="form-control" id="newpasswordrepeat" name="newwpasswordrepeat" placeholder="Password">
    </div>
    <br />
    <input type="hidden" id="action" name="action" value="signup">
    <button type="submit" class="btn btn-secondary">Register</button>
    </form>
    <pre>
    <?php
    //print_r($_REQUEST);
    ?>
    </pre>
</body>
</html>