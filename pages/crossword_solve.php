<?php
    use Crosswords\Crossword, Crosswords\PlacedClue, UI\DisplayMessage;
    
    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $crossword = null;

    if (!isset($params[0])) {
        $error_messages[] = "You need to choose which crossword to solve.";
        $fatal_error = true;
    } elseif (!is_numeric($params[0])) {
        $error_messages[] = "{$params[0]} isn't a valid crossword id.";
        //DisplayMessage::add("{$params[0]} isn't a valid crossword id.", DisplayMessage::LVL_ERROR, 1);
        $fatal_error = true;
    } else {
        $crossword = Crossword::getById($params[0]);
        if ($crossword->user_id != $_SESSION['USER_ID']) {
            $error_messages[] = "You can only solve crosswords that you created or that have been shared publicly.";
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
    // No modals needed as yet
?>

<!-- Set vars -->
<?php
echo '<script type="application/json" class="data-transfer" data-scope="window">';
    $data = [
        'root_path' => $config['root_path'],
        'crossword_id' => $crossword->id,
        'user_id' => $user->id,
    ];
    echo json_encode($data);
    echo '</script>';
?>
<!-- Dictionary mgmt -->
<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/dexie/4.0.8/dexie.min.js'></script>
<script type="text/javascript" src='~ROOT~/js/class/Tome.js'></script>
<script type="text/javascript" src='~ROOT~/js/class/TomeEntry.js'></script>
<script type="text/javascript" src='~ROOT~/js/dict_master.js'></script>

<!-- Ajax cue -->
<div id="ajaxCue">
    <div id="ajaxCount"></div>
</div>
<!-- Title etc -->
<div style="float:right;">
    <button type="button" id="print__Trigger" class="btn btn-success"><span class="bi bi-printer-fill"
            aria-hidden="true"></span></button>
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