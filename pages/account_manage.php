<script type='text/javascript'>
    $(document).ready( function() {
        $('#deleteAccountAreYouSure').on('change', function() {
            if ($('#deleteAccountAreYouSure').is(':checked')) {
                $('#deleteAccountButton').removeAttr('disabled');
            } else {
                $('#deleteAccountButton').attr('disabled','disabled');
            }
        });
    } );
</script>
<?php

$fatal_error = false;
$error_messages = [];
$info_messages = [];
if (isset($_REQUEST['error_message'])) {
    $error_messages[] = $_REQUEST['error_message'];
}
if (isset($_SESSION['USER_ID'])) {
    // Look up user from db
    $user = User::getById($_SESSION['USER_ID']);
    if ($user == null) {
        $error_messages[] = "Cannot find your user account in the database. Please try logging in again.";
        $fatal_error = true;
        header("Location: {$config['root_path']}/logout.php?error_message=".implode(',',$error_messages));
        die();
    }
} else {
    $error_messages[] = "You need to log in.";
    $fatal_error = true;
    header("Location: {$config['root_path']}/logout.php?error_message=".implode(',',$error_messages));
    die();
}
if ($fatal_error) {
    foreach($error_messages as $error_message) {
        echo "\t<div class='row'>\n";
        echo "\t\t<div class='span12 alert alert-danger'>{$error_message}</div>\n";
        echo "\t</div>\n";
        die();
    }
} else {
    // Handle form submission
    if (isset($_REQUEST['action'])) {

        if ($_REQUEST['action'] == 'formsubmitted') {
            if (empty($_REQUEST['displayName'])) {
                $error_messages[] = "Your name cannot be empty.";
            } else {
                if (empty($_REQUEST['email'])) {
                    $error_messages[] = "Your email address cannot be empty.";
                } else {
                    // Save user
                    $user->display_name = $_REQUEST['displayName'];
                    $user->email = $_REQUEST['email'];
                    $user->save();
                    $info_messages[] = "Your changes were saved.";
                    $user = User::getById(($_SESSION['USER_ID'])); // Just to be sure that the save DID occur!
                }
            }
        }
        
        if ($_REQUEST['action'] == 'accountdeleted') {
            if (empty($_REQUEST['deleteAccountAreYouSure'])) {
                $error_messages[] = "You must confirm deletion.";
            } else {
                // Delete crosswords
                $crosswords = Crossword::find(['user_id','=',$_SESSION['user_id']]);
                foreach($crosswords as $crossword) {
                    $crossword->delete();
                }
                // Delete user
                $user->delete();
                session_destroy(); // Lose login
                header('Location: '.$config['root_path'].'/?error_message='.urlencode("Your account has been deleted."));
            }
        }
    }

    if (count($error_messages) > 0) {
        foreach($error_messages as $error_message) {
            echo "\t<div class='row'>\n";
            echo "\t\t<div class='span12 alert alert-danger'>{$error_message}</div>\n";
            echo "\t</div>\n";
            die();
        }
    }
    if (count($info_messages) > 0) {
        foreach($info_messages as $info_message) {
            echo "\t<div class='row'>\n";
            echo "\t\t<div class='span12 alert alert-info'>{$info_message}</div>\n";
            echo "\t</div>\n";
            die();
        }
    }
?>
<!-- ACCOUNT FORM -->
<div class='card'>
    <div class='card-body'>
        <h3 class='card-title'>My Account</h3>
        <form method='POST'>
            <div class="row">
                <div class="col-12 fs-5 fst-italic">None of these changes will affect your Spotify account</div>
            </div>  
            <div class="row">
                <div class="col-12">
                    <label for="displayName" class="form-label">Your name</label>
                    <input type="text" class="form-control" id="displayName" name="displayName" aria-describedby="displayNameHelp" value="<?= str_replace('"','\\'.'"',$user->display_name) ?>">
                    <div id="displayNameHelp" class="form-text">How you appear to other NW users.</div>
                    
                    <label for="email" class="form-label">Your email address</label>
                    <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" value="<?= str_replace('"','\\'.'"',$user->email) ?>">
                    <div id="emailHelp" class="form-text">Your email address won't be passed on to anyone else.</div>

                    <input type="hidden" name="action" value="formsubmitted">
                    
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- DELETE ACCOUNT -->
<div class='card'>
    <div class='card-body'>
        <h3 class='card-title'>Delete Account</h3>
        <form method='POST'>
            <div class="row">
                <div class="col-12 fs-5 fst-italic">If you want to remove your account, click here.<br />
                You will lose all your NW crosswords, but any community contributions will remain.</div>
            </div>  
            <div class="row">
                <div class="col-12">
                    <label for="deleteAccountAreYouSure" class="form-label">Are you sure?</label>
                    <input class="form-check-input" id="deleteAccountAreYouSure" name="deleteAccountAreYouSure" aria-describedby="deleteAccountAreYouSureHelp" type="checkbox">
                    <div id="deleteAccountAreYouSureHelp" class="form-text">Tick this box, then click <strong>Delete</strong> below.</div>
                    
                    <input type="hidden" name="action" value="accountdeleted">
                    
                    <button type="submit" class="btn btn-danger" id='deleteAccountButton' disabled>Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
}