<?php
    use Crosswords\Crossword, Crosswords\PlacedClue, UI\DisplayMessage;

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

<!-- MODALS -->
<?php
    $form_new_clue = new UI\BootstrapForm('new-clue');
    $form_new_clue->addField('row')->setLabel('Starting row')->setType("number")->setDivClass('mb-3')->setClass('focussed-input border-secondary')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>0,'max'=>$crossword->rows-1])->setHelp("(starting at row 0)");
    $form_new_clue->addField('col')->setLabel('Starting column')->setType("number")->setDivClass('mb-3')->setClass('border-secondary')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>0,'max'=>$crossword->cols-1])->setHelp("(starting at column 0)");
    $form_new_clue->addField('orientation')->setLabel('Orientation')->setType('select')->setDivClass('border-bottom mb-3')->setClass('border-secondary')->setOptions([PlacedClue::ACROSS=>'Across',PlacedClue::DOWN=>'Down'])->setValue('Across'); /*->setHelp("Across or Down")*/
    $form_new_clue->addField('answer')->setLabel('Answer')->setDivClass('mb-3')->setClass('border-secondary')->setStyle("text-transform:uppercase"); /*->setHelp("The answer to the cryptic clue, including spaces, punctuation, etc.")*/
    $form_new_clue->addField('pattern')->setLabel('')->setType('hidden');
    $form_new_clue->addField('clue')->setLabel('Clue')->setDivClass('mb-3')->setClass('border-secondary'); /*->setHelp("The clue text")*/
    $form_new_clue->addField('explanation')->setLabel('Explanation')->setClass('border-secondary'); /*->setHelp("An explanation of the working of the clue (optional, will not show on crossword output)")*/
    $modal_new_clue = new UI\BootstrapModal('new-clue');
    $modal_new_clue->setTitle('Add clue')
    ->setBody($form_new_clue->getHtml())
    ->setButtons("Save")
    ->setTrigger("+");
    echo $modal_new_clue->getMainHtml();
    
    $form_edit_clue = new UI\BootstrapForm('edit-clue');
    $form_edit_clue->addHtml("<div class='' id='form-edit-clue-affected-clues-warning'>This may also affect: <span id='form-edit-clue-affected-clues-details'></span></div>");
    $form_edit_clue->addField('id')->setType("hidden");
    $form_edit_clue->addField('row')->setLabel('Starting row')->setType("number")->setDivClass('mb-3')->setClass('focussed-input border-secondary')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>0,'max'=>$crossword->rows-1])->setHelp("(starting at row 0)");
    $form_edit_clue->addField('col')->setLabel('Starting column')->setType("number")->setDivClass('mb-3')->setClass('border-secondary')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>0,'max'=>$crossword->cols-1])->setHelp("(starting at column 0)");
    $form_edit_clue->addField('orientation')->setLabel('Orientation')->setType('select')->setDivClass('border-bottom mb-3')->setClass('border-secondary')->setOptions([PlacedClue::ACROSS=>'Across',PlacedClue::DOWN=>'Down'])->setValue('Across'); /*->setHelp("Across or Down")*/
    $form_edit_clue->addField('answer')->setLabel('Answer')->setDivClass('mb-3')->setClass('border-secondary')->setStyle("text-transform:uppercase"); /*->setHelp("The answer to the cryptic clue, including spaces, punctuation, etc.")*/
    $form_edit_clue->addField('pattern')->setLabel('')->setType('hidden');
    $form_edit_clue->addField('clue')->setLabel('Clue')->setDivClass('mb-3')->setClass('border-secondary'); /*->setHelp("The clue text")*/
    $form_edit_clue->addField('explanation')->setLabel('Explanation')->setClass('border-secondary'); /*->setHelp("An explanation of the working of the clue (optional, will not show on crossword output)")*/
    $modal_edit_clue = new UI\BootstrapModal('edit-clue');
    $modal_edit_clue->setTitle('Edit clue')
    ->setBody($form_edit_clue->getHtml())
    ->setButtons("Save");
    echo $modal_edit_clue->getMainHtml();
    
    $form_edit_settings = new UI\BootstrapForm('edit-settings');
    //$form_edit_settings->addHtml("<div class='' id='form-edit-settings-affected-settingss-warning'>This may also affect: <span id='form-edit-settings-affected-settingss-details'></span></div>");
    $form_edit_settings->setColumns(2);
    $form_edit_settings->addField('id')->setType("hidden")->setValue($crossword->id);
    $form_edit_settings->addField('title')->setValue($crossword->title)->setLabel('Title')->setDivClass('mb-3')->setClass('focussed-input border-secondary')->setStylePreset('floating');
    $form_edit_settings->addField('old_rows')->setValue($crossword->rows)->setType("hidden")->setDivClass('')->setClass('')->setAdditionalAttributes(['disabled'=>'disabled']);
    $form_edit_settings->addField('old_cols')->setValue($crossword->cols)->setType("hidden")->setDivClass('')->setClass('')->setAdditionalAttributes(['disabled'=>'disabled']);
    $form_edit_settings->addField('rows')->setValue($crossword->rows)->setLabel('Row count')->setColumn(1)->setType("number")->setDivClass('mb-3')->setClass('border-secondary alters-trim')->setStylePreset('floating')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>1,'max'=>50])->setHelp("(will affect existing clues)");
    $form_edit_settings->addField('cols')->setValue($crossword->cols)->setLabel('Column count')->setColumn(2)->setType("number")->setDivClass('mb-3')->setClass('border-secondary alters-trim')->setStylePreset('floating')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>1,'max'=>50])->setHelp("(will affect existing clues)");
    $form_edit_settings->addField('trim_top')->setValue(0)->setLabel('Trim top')->setType("number")->setColumn(1)->setDivClass('mb-3')->setClass('border-secondary alters-trim')->setStylePreset('floating')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>0,'max'=>$crossword->rows-1])->setHelp("");
    $form_edit_settings->addField('trim_left')->setValue(0)->setLabel('Trim left')->setType("number")->setColumn(2)->setDivClass('mb-3')->setClass('border-secondary alters-trim')->setStylePreset('floating')->setStyle('max-width: 10em;')->setAdditionalAttributes(['min'=>0,'max'=>$crossword->cols-1])->setHelp("");
    $form_edit_settings->addField('trim_bottom')->setValue(0)->setLabel('Trim bottom')->setType("number")->setColumn(1)->setDivClass('mb-3')->setClass('border-secondary alters-trim')->setStylePreset('floating')->setStyle('max-width: 10em;')->setAdditionalAttributes(['readonly'=>'readonly'])->setHelp("");
    $form_edit_settings->addField('trim_right')->setValue(0)->setLabel('Trim right')->setType("number")->setColumn(2)->setDivClass('mb-3')->setClass('border-secondaryalters-trim')->setStylePreset('floating')->setStyle('max-width: 10em;')->setAdditionalAttributes(['readonly'=>'readonly'])->setHelp("");
    $form_edit_settings->addField('rotational-symmetry-order')->setLabel('Symmetry')->setType('select')->setDivClass('border-bottom mb-3')->setClass('border-secondary')->setStylePreset('floating')->setOptions(['2'=>'2-fold','4'=>'4-fold'])->setValue($crossword->rotational_symmetry_order.'-fold'); /*->setHelp("Across or Down")*/
    $modal_edit_settings = new UI\BootstrapModal('edit-settings');
    $modal_edit_settings->setTitle('Edit settings')
    ->setBody($form_edit_settings->getHtml())
    ->setButtons("Save")
    ->setTrigger("<span class='bi bi-gear-fill' aria-hidden='true'></span>");
    echo $modal_edit_settings->getMainHtml();
?>



<!-- Dictionary mgmt -->
<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/dexie/4.0.8/dexie.min.js'></script>
<!-- TODO - use ES6 modules so we don't have to include each js file -->
<script type="text/javascript" src='../../js/class/Tome.js'></script>
<script type="text/javascript" src='../../js/class/TomeEntry.js'></script>
<script type="text/javascript" src='../../js/dict_master.js'></script>

<!-- Set vars -->
<script type="text/javascript">
<?php
    echo "var root_path = \"{$config['root_path']}\";\n";
    echo "var crossword_id = {$crossword->id};\n";
    echo "var currentUser = {$user->id};\n";
?>
</script>

<!-- Ajax cue -->
<div id="ajaxCue"><div id="ajaxCount"></div></div>
<!-- Title etc -->
<div style="float:right;">
    <?= $modal_edit_settings->getTriggerHtml(); ?>
</div>
<h2 class="text-center">
    <?= $crossword->title ?>
</h2>
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

<!-- CONTEXT MENUS -->
<?php
    $menu_grid_square = new UI\BootstrapContextMenu('menu-grid-square');
    $menu_grid_square->setItems( new UI\BootstrapMenuItem_List( [
        new UI\BootstrapMenuItem('new-clue-across',"New across clue"),
        new UI\BootstrapMenuItem('edit-clue-across',"Edit across clue"),
        new UI\BootstrapMenuItem('delete-clue-across', "Delete across clue"),
        new UI\BootstrapMenuItem('new-clue-down', "New down clue"),
        new UI\BootstrapMenuItem('edit-clue-down', "Edit down clue"),
        new UI\BootstrapMenuItem('delete-clue-down', "Delete down clue"),
        new UI\BootstrapMenuItem('clear-grid-square', "Clear this square"),
    ] ) );
    echo $menu_grid_square->getHtml();
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
        <table class='d-none'>
            <tr id='clue-row-template' class='clue-row' data-clue-orientation='' data-clue-number='0'>
                <td class='clue-number'>&nbsp;</td>
                <td class='clue-question'>&nbsp;</td>
            </tr>
        </table>
        <div>
            <?= $modal_new_clue->getTriggerHtml(); ?>
        </div>
        <div class='clue-container'>
            <?= $crossword->getCluesHtml(true); ?>
        </div>
    </div>
</div>