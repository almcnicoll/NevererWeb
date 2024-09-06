<?php
    $fatal_error = false;

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
        $fatal_error = true;
    } else {
        $crossword = Crossword::getById($params[0]);
        if ($crossword->user_id != $_SESSION['USER_ID']) {
            $error_messages[] = "You can only edit crosswords that you created.";
            $fatal_error = true;
        }
    }

    if ($fatal_error) {
        // If we can't edit, redirect to index and give error messages
        header("Location: {$config['root_path']}/?error_message=".urlencode(implode(',',$error_messages)));
        die();
    }

    if (isset($_SESSION['PAGE_LOADCOUNTS'][__FILE__])) {
        $_SESSION['PAGE_LOADCOUNTS'][__FILE__]++;
    } else {
        $_SESSION['PAGE_LOADCOUNTS'][__FILE__] = 1;
    }
?>
<!-- Set vars -->
<script type="text/javascript">
<?php
    echo "var root_path = \"{$config['root_path']}\";\n";
    echo "var crossword_id = {$crossword->id};\n";
    echo "var currentUser = {$user->id};\n";
?>
</script>

<!-- Title etc -->
<h2 class="text-center"><?= $crossword->title ?></h2>
<?php
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
?>

<!-- MODALS -->
<?php
    $form_new_clue = new UI\BootstrapForm('new-clue');
    $form_new_clue->addField('row')->setLabel('Starting row')->setType("number")->setDivClass('mb-3')->setClass('focussed-input border-secondary')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>1,'max'=>$crossword->rows]); /*->setHelp("The row in which the clue starts")*/
    $form_new_clue->addField('col')->setLabel('Starting column')->setType("number")->setDivClass('mb-3')->setClass('border-secondary')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>1,'max'=>$crossword->cols]); /*->setHelp("The column in which the clue starts")*/
    $form_new_clue->addField('orientation')->setLabel('Orientation')->setType('select')->setDivClass('border-bottom mb-3')->setClass('border-secondary')->setOptions([PlacedClue::ACROSS=>'Across',PlacedClue::DOWN=>'Down'])->setValue('Across'); /*->setHelp("Across or Down")*/
    $form_new_clue->addField('answer')->setLabel('Answer')->setDivClass('mb-3')->setClass('border-secondary')->setStyle("text-transform:uppercase"); /*->setHelp("The answer to the cryptic clue, including spaces, punctuation, etc.")*/
    $form_new_clue->addField('pattern')->setLabel('')->setType('hidden');
    $form_new_clue->addField('clue')->setLabel('Clue')->setDivClass('mb-3')->setClass('border-secondary'); /*->setHelp("The clue text")*/
    $form_new_clue->addField('explanation')->setLabel('Explanation')->setClass('border-secondary'); /*->setHelp("An explanation of the working of the clue (optional, will not show on crossword output)")*/
    $modal_new_clue = new UI\BootstrapModal('new-clue');
    $modal_new_clue->setTitle('Add clue')
    ->setBody($form_new_clue->getHtml())
    ->setButtons("Save")
    ->setTrigger("Add");
    echo $modal_new_clue->getMainHtml();
?>

<div class='row'>
    <div class='col-md-6'>
        <div class='crossword-container'>
        <?php
        echo $crossword->getGridHtml(true);
        ?>
        </div>
    </div>
    <div class='col-md-6'>
        <div class='clue-container'>
        <?= $crossword->getCluesHtml(true); ?>
        </div>
        <div><?= $modal_new_clue->getTriggerHtml(); ?></div>
    </div>
</div>