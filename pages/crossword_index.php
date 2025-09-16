<?php
use Crosswords\Crossword;

$error_messages = [];
if (isset($_REQUEST['error_message'])) {
    $error_messages[] = $_REQUEST['error_message'];
}

if (isset($_REQUEST['newname'])) {
    $user->display_name = $_REQUEST['newname'];
    $user->save();
}

echo <<<END_SCRIPTS
<!-- Set variable -->
<script type='text/javascript'>
if (typeof(root_path) === 'undefined') { var root_path = "{$config['root_path']}"; }
</script>
<!-- Include crossword-delete script -->
<script src='js/delete_handler.js'></script>
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

if ($user->display_name) {
?>
<div class="row">
    <div class="col-12">
        <h1>Welcome, <?= $user->display_name ?></h1>
    </div>
</div>
<?php
} else {
?>
<div class="row">
    <div class="col-12">
        <h1>Welcome!</h1>
    </div>
</div>
<form method="POST">
    <div class="mb-3">
        <label for="newname" class="form-label">What's your name?</label>
        <input type="text" class="form-control" name="newname" id="newname" aria-describedby="newname-help">
        <div class="form-text" id="newname-help">It's helpful to have your name!</div>
        <button type="submit" class="btn btn-primary">Update!</button>
    </div>
</form>
<hr />
<?php
}
?>
<div class='card text-bg-dark'>
    <div class='card-body'>
        <?php
// List all crosswords
$criteriaMine = ['user_id','=',$_SESSION['USER_ID']];
$my_crosswords = Crossword::find($criteriaMine);

if (count($my_crosswords)==0) {
    // No crosswords of our own
?>

        <h2 class='card-title'>Your crosswords</h2>
        <div class="row">
            <div class="col-12">
                <h3>You don't have any crosswords. How sad!</h3>
                <h4>Click below to create one.</h4>
                <a class="btn btn-primary" href="crossword/create">Create</a>
            </div>
        </div>
        <?php
} else {
    // At least one crossword of our own
?>

        <div class="d-grid gap-2 d-md-block">
            <h2 class='card-title'>Your crosswords
                <a class="btn btn-primary mb-1 col-1" href="<?= $config['root_path'] ?>/crossword/create">+ New</a>
                <a class="btn btn-success mb-1 col-1" href="<?= $config['root_path'] ?>/crossword/import">+ Import</a>
            </h2>
        </div>



        <table class="table table-sm table-striped table-hover crossword-table" id="my-crosswords-table">
            <thead>
                <tr>
                    <th>Crossword</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
    foreach ($my_crosswords as $crossword) {
        if ($crossword->title==null) {
            $filename = $crossword->id.'.json';
        } else {
            $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $crossword->title); // Illegal chars
            $filename = mb_ereg_replace("([\.]{2,})", '', $filename); // Runs of periods (dir-changing)
            $filename .= "-{$crossword->id}.json";
        }
?>
                <tr style='vertical-align: middle;'>
                    <th scope='row'>
                        <div class='cell-container'><?=$crossword->title?></div>
                    </th>
                    <td>
                        <div class='cell-container'><?=$playlist->created?></div>
                    </td>
                    <td>
                        <div class='row'>
                            <div class='col-md-6'>
                                <a href='crossword/edit/<?=$crossword->id?>' title='Edit crossword'
                                    class='btn btn-md btn-warning'><span class='bi bi-pencil-square'
                                        role='edit'></span></a>
                                <a href='crossword/share/<?=$crossword->id?>' title='Share crossword'
                                    class='btn btn-md btn-primary'><span class='bi bi-share'></span></a>
                            </div>
                            <div class='col-md-6'>
                                <a href='crossword/export/<?=$crossword->id?>' download="<?= $filename ?>"
                                    title='Export crossword' class='btn btn-md btn-success'><span
                                        class='bi bi-download'></span></a>
                                <a href='#' class='btn btn-md btn-danger' title='Delete crossword'
                                    data-bs-toggle='modal' data-bs-target='#crosswordDeleteModal' role='delete'
                                    data-id='<?=$crossword->id?>'><span class='bi bi-trash3'></span></a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
    }
?>
            </tbody>
        </table>
        <?php
}
?>
    </div> <!-- CARD-BODY -->
</div> <!-- CARD -->

<div class="modal fade" id="crosswordDeleteModal" tabindex="-1">
    <div class="modal-dialog .modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Crossword</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    id="deleteModalCloseX"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <!-- Where would you like to delete the playlist from? -->Are you sure you want to delete the
                        crossword?
                    </div>
                </div>
                <div class="row">
                    <div class="col-6-md p-2">
                        <a class='btn btn-md btn-danger' id='deleteHere' style='width: 100%;'>Yes</a>
                    </div>
                    <div class="col-6-md p-2">
                        <button class='btn btn-md btn-success' id='deleteCancel' data-bs-dismiss="modal"
                            style='width: 100%;'>Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>