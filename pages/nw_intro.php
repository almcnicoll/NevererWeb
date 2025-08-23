<?php
// TODO - find a more universal way of displaying error messages (we have a makeToast() javascript function if we can get the messages there)
//  as well as a better way of storing them (session variable? class variable? PageInfo?) 
if (!isset($error_messages)) { $error_messages = []; }
if (isset($_REQUEST['error_message'])) { $error_messages[] = $_REQUEST['error_message']; }
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
<div class='card text-bg-dark'>
    <div class='card-body bg-primary'>
        <h1 class='card-title'>The Neverer Crossword Maker</h1>
        <h2 class='card-title'>Web Edition</h2>
    </div>
</div>
<br />
<div class='card'>
    <div class='card-body'>
        <h3 class='card-title'>How it works</h3>
        <ol class='fs-4'>
            <?php if (isset($_SESSION['USER'])): ?>
            <li class='step-done'>Sign in / register</li>
            <?php else: ?>
            <li><a href="<?= $config['root_path'] ?>/login.php">Sign in / register</a></li>
            <div class="fs-6 step-explain">You'll need to create an account to use the software</div>
            <?php endif; ?>
            <li>Enter the basic details for your new crossword</li>
            <div class="fs-6 step-explain">A title for the crossword, its size, and the number of axes of symmetry</div>
            <li>Get creating!</li>
        </ol>
        <a href="<?= $config['root_path'] ?>/" class="btn btn-md btn-success" id='btn-assign-letters'>Let's go!
            &gt;&gt;</a>
    </div>
</div>