<?php
use Crosswords\Crossword;
?>
<h2 class="text-center">Great! Now share the love...</h2>
<?php
$error_messages = [];
if (isset($_REQUEST['error_message'])) {
    $error_messages[] = $_REQUEST['error_message'];
}

$crossword = null;

if (!isset($params[0])) {
    $error_messages[] = "You need to choose which crossword to edit.";
    $fatal_error = true;
} elseif (!is_numeric($params[0])) {
    $error_messages[] = "{$params[0]} isn't a valid crossword id.";
    //DisplayMessage::add("{$params[0]} isn't a valid crossword id.", DisplayMessage::LVL_ERROR, 1);
    $fatal_error = true;
} else {
    $crossword = Crossword::getById($params[0]);
    if ($crossword->user_id != $_SESSION['USER_ID']) {
        $error_messages[] = "You can only edit crosswords that you created.";
        //DisplayMessage::add("You can only edit crosswords that you created.", DisplayMessage::LVL_ERROR, 1);
        $fatal_error = true;
    }
}

echo <<<END_SCRIPTS
<!-- Set variable -->
<script type='text/javascript'>
if (typeof(root_path) === 'undefined') { var root_path = "{$config['root_path']}"; }
</script>
END_SCRIPTS;

// Display error messages
if (count($error_messages)>0) {
    foreach($error_messages as $error_message) {
?>
<div class="row">
    <div class="span12 alert alert-danger"><?= $error_message ?></div>
</div>
<?php
    }
}
?>

<?php
if ($fatal_error) {
    die();
}

$share_link = $config['root_path'].'/crossword/solve/'.$crossword->id;
?>
<div class="row text-center">
    <div class="col-md-6">
        <h3>Share on</h3>
        <div class="row text-center fs-1">
            <div class="col">
                <a href="whatsapp://send?text=Check%20out%20my%20new%20crossword%21%20<?= $share_link ?>"
                    data-action="share/whatsapp/share" target="_blank" class="">
                    <span class="bi bi-whatsapp" style="color: #25D366;"></span>
                </a>
            </div>
            <div class="col">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $share_link ?>"
                    data-action="share/facebook/share" target="_blank" class="">
                    <span class="bi bi-facebook" style="color: #4267B2;"></span>
                </a>
            </div>
            <div class="col">
                <a href="mailto:?subject=Check%20out%20my%20new%20crossword%21&body=<?= $share_link ?>"
                    data-action="share/email/share" target="_blank" class="">
                    <span class="bi bi-envelope" style="color: #000;"></span>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <h3>or copy link</h3>
        <div class="row fs-3 text-center">
            <div class="col-sm-12">
                <div class="input-group mt-3">
                    <input type="text" class="form-control" id="share-link-input" value="<?= $share_link ?>">
                    <button class="btn btn-outline-secondary copy-button" data-copy-source="share-link-input"
                        type="button" id="copy-button">Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12 text-center">
        <a class="btn btn-lg btn-success" href="<?= $config['root_path'].'/' ?>">Back</a>
        <p class="fs-5 text-body-secondary">Done sharing? Click here to return.</p>
    </div>
</div>