<?php
// TODO - find a more universal way of displaying error messages (perhaps using header.php?)
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

// Set questions
$config = Config::get();
$faqs = FAQ::getAll();
?>
<div class='card text-bg-dark'>
    <div class='card-body bg-primary'>
        <h1 class='card-title'>Destination Playlist</h1>
        <h2 class='card-title'>Music for the journey</h2>
    </div>
</div>
<br />
<div class='card'>
    <div class='card-body'>
        <h3 class='card-title'>FAQ</h3>
        <div class="mb-3 accordion" id="faqAccordion">
            <div class="accordion-item">
<?php
$i = 0;
foreach ($faqs as $faq) {
    $question = $faq->question;
    $answer = $faq->answer;
    $i = $faq->id;
?>
                <h2 class="accordion-header">
                    <button class="accordion-button <?= (($i==1)?'':'collapsed') ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQ<?= $i ?>" aria-expanded="<?= (($i==1)?'true':'false') ?>" aria-controls="collapseFAQ<?= $i ?>">
                        <?= $question ?>
                    </button>
                </h2>
                <div class="accordion-collapse collapse <?= (($i==1)?'show':'') ?>" data-bs-parent="#faqAccordion" id="collapseFAQ<?= $i ?>">
                    <?= $answer ?>
                </div>
<?php
}
?>
            </div>
        </div>
        <a href="<?= $config['root_path'] ?>/" class="btn btn-md btn-success" id='btn-assign-letters'>Get started! &gt;&gt;</a>
    </div>
</div>