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
require_once('vendor/autoload.php');
require_once('autoload.php');
?>
<pre>
<?php
print_r($_REQUEST);
?>
</pre>
</body>
</html>