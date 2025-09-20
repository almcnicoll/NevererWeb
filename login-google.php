<?php
    require_once('vendor/autoload.php');
    require_once('autoload.php');

    $client = new Google\Client();
    $client->setApplicationName("NevererWeb");
    $client->setClientId($config['GOOGLE_CLIENTID']);
    $client->setClientSecret($config['GOOGLE_CLIENTSECRET']);
    //$client->setDeveloperKey("...");
    if (isset($_GET['code'])) {
        // First return from Google - has auth code
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            
        // Check if the token was successfully fetched.
        if (isset($token['error'])) {
            // Handle the error (e.g., the user denied access).
            echo 'Authentication failed: ' . $token['error']; // TODO Do this better
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
        //TODO - code to either create or look up account

    } else {
        // Initial page load
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $redirect_uri = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $client->setRedirectUri($redirect_uri);
        $client->addScope('email');
        $client->addScope('profile');
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
<pre>
<?php
print_r($_REQUEST);
?>
</pre>
</body>
</html>