<?php
    require_once('autoload.php');
    require_once('vendor/autoload.php');
    use Security\User;
    use Security\AuthMethod;
    use Google\Client;
    use Google\Service\Oauth2;

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $redirect_uri = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    $client = new \Google\Client();
    $client->setApplicationName("NevererWeb");
    $client->setClientId($config['GOOGLE_CLIENTID']);
    $client->setClientSecret($config['GOOGLE_CLIENTSECRET']);
    $client->setRedirectUri($redirect_uri);
    $client->addScope('email');
    $client->addScope('profile');
    
    if (isset($_GET['code'])) {
        // Return from Google - we have an auth code
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Check if the token was successfully fetched.
        if (isset($token['error'])) {
            // Handle the error (e.g., the user denied access).
            echo 'Authentication failed: ' . $token['error']; // TODO Do this better
            echo "<pre>".print_r($token,true)."</pre>";
            exit;
        }
        
        // Store the access token in the session for later use.
        $client->setAccessToken($token);
        // Create an instance of the Oauth2 service.
        $oauth2 = new Google\Service\Oauth2($client);        
        // Get the user's profile information.
        $userInfo = $oauth2->userinfo->get();
        $google_name = $userInfo->name;
        $google_email = $userInfo->email;
        // Either create or look up account
        $foundUser = User::findFirst(['identifier','=',$google_email]);
        if ($foundUser == null) {
            $user = new User();
            $user->setAuthmethod_id(AuthMethod::findFirst(['methodName', '=', 'google'])->id);
            $user->identifier = $google_email;
            $user->email = $google_email;
            $user->display_name = $google_name;
            //echo "<pre>".print_r($user,true)."</pre>";
            $user->save();
            // Log them in
            $_SESSION['USER_ID'] = $user->id;
            $_SESSION['USER'] = serialize($user);
            $_SESSION['USER_AUTHMETHOD_ID'] = $user->authmethod_id;
            $_SESSION['USER_ACCESSTOKEN'] = null;
            $_SESSION['USER_REFRESHTOKEN'] = null;
            $_SESSION['USER_REFRESHNEEDED'] = strtotime('2100-01-01 00:00:00'); // Never expire
            //echo "<pre>Session:\n".print_r($_SESSION,true)."</pre>";
        } else {
            $_SESSION['USER_ID'] = $foundUser->id;
            $_SESSION['USER'] = serialize($foundUser);
            $_SESSION['USER_AUTHMETHOD_ID'] = $foundUser->authmethod_id;
            $_SESSION['USER_ACCESSTOKEN'] = $token;
            $_SESSION['USER_REFRESHTOKEN'] = null; // TODO - get and store this?
            $_SESSION['USER_REFRESHNEEDED'] = strtotime('2100-01-01 00:00:00'); // Never expire
        }
            session_write_close();
            // redirect is getting problematic... it triggers when all kinds of resources are requested, and therefore redirects us to e.g. stylesheets on logon! //TODO - fix this!
            // if (isset($_SESSION['redirect_url_once'])) {
            //     header('Location: '.$_SESSION['redirect_url_once']);
            //     unset($_SESSION['redirect_url_once']);
            // } else {
            //     header('Location: ./');
            // }
            header('Location: ./');
            die();
    } else {
        // Initial page load
        // Generate the authorization URL.
        $authUrl = $client->createAuthUrl();
        // Redirect the user to the authorization URL.
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (substr(@$config['root_path'],0,strlen('http://localhost'))=='http://localhost' ? 'LOCAL ':'') ?>Login to Neverer Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
</head>
<body>
</body>
</html>