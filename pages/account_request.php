<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (isset($_REQUEST['your_name'])) {
    if (!isset($config)) { $config = Config::get(); }
    $mail = new PHPMailer(true);
    try {
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host = 'neverer.uk';
        $mail->SMTPAuth = true;
        $mail->Username = $config['MAIL_USERNAME'];
        $mail->Password = $config['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('admin@neverer.uk', 'NW Administrator');
        //$mail->addAddress('admin@neverer.uk', 'NW Administrator');
        $mail->isHTML(true);
        $mail->Subject = "NW Access Request";
        $mail->Body = "<p>Access request from Neverer Web</p><pre>".print_r($_REQUEST,true)."</pre>";
        $mail->AltBody = "<p>Access request from Neverer Web:\r\n".print_r($_REQUEST,true);
        $mail->send();
        ?>
        <div class='card'>
            <div class='card-body border border-4 border-success rounded'>
                <h3 class='card-title'>Thanks!</h3>
                <p>Your request has been sent. You'll get an email to <?= $_REQUEST['email'] ?> when your account has been approved.</p>
            </div>
        </div>
        <br />
        <?php
    } catch (Exception $e) {
        ?>
        <div class='card'>
            <div class='card-body border border-4 border-danger rounded'>
                <h3 class='card-title'>Apologies!</h3>
                <p>Your request could not be sent, for reasons as yet uncertain. Please try again later.</p>
            </div>
        </div>
        <br />
        <?php
    }
    /*mail('almcnicoll@gmail.com',
        "NW Access Request",
        "Output from request form:\r\n\r\n".print_r($_REQUEST,true),
        $mailHeaders);*/
  
} else {
    if (isset($params) && count($params)>0) {
        if ($params[0] == 403) {
            // Output explanatory text
?>
<div class='card'>
    <div class='card-body border border-4 border-info rounded'>
        <h3 class='card-title'>You need to request an account</h3>
        <p>This app is currently in <strong>development mode</strong>. This means that I have to manually add people to a list of users before they can use the app.</p>
        <p>Once it's been fully tested, I hope it'll be approved and this step won't be necessary.</p>
        <p>To use the app, please fill in the form below.</p>
    </div>
</div>
<br />
<?php
        }
    }
}

$currUser = null; $currName = ''; $currEmail = '';
if (isset($_SESSION['USER'])) {
    $currUser = $_SESSION['USER'];
    $currName = $currUser->display_name;
    $currEmail = $currUser->email;
}
?>

<div class='card'>
    <div class='card-body'>
        <h3 class='card-title'>Request access</h3>
        <form method='POST'>
            <div class="mb-3">
                <label for="your_name" class="form-label">What's your name?</label>
                <input type="text" class="form-control" name="your_name" id="your_name" aria-describedby="your_name-help" required value="<?= $currName ?>">
                <div class="form-text" id="your_name-help">Hopefully no explanation needed.</div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">What's your Spotify email address?</label>
                <input type="email" class="form-control" name="email" id="email" aria-describedby="email-help" required value="<?= $currEmail ?>">
                <div class="form-text" id="email-help">This has to be the email address you've given Spotify for your account to work properly.</div>
            </div>
            <div class="mb-3">
                <label for="but_how" class="form-label">How did you hear about Destination Playlist?</label>
                <textarea type="text" class="form-control" name="but_how" id="but_how" aria-describedby="but_how-help"></textarea>
                <div class="form-text" id="but_how-help">Partly I'm curious; partly it's useful to know which user referred you.</div>
            </div>
            <div class="mb-3">
                <button type='submit' class='btn btn-lg btn-primary'>Sign me up!</button>
            </div>
        </form>
    </div>
</div>