<?php
    use Dictionaries\Tome;
use Dictionaries\TomeEntry;
use Security\User;
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

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

    // Retrieve list of Tomes
    $readable_tomes = Tome::getAllForUser($_SESSION['USER_ID']);
    $mine = array_filter($readable_tomes, fn($t) => $t->user_id == $_SESSION['USER_ID']);
    $public = array_filter($readable_tomes, fn($t) => $t->user_id != $_SESSION['USER_ID']);
?>
<h2>Dictionary Management</h2>

<div class='card text-bg-dark'>
    <div class='card-body'>
        <div class="d-grid gap-2 d-md-block">
            <h2 class='card-title'>Public Dictionaries</h2>
        </div>
        <table class="table table-sm table-striped table-hover crossword-table" id="public-dictionaries-table">
            <thead>
                <tr>
                    <th>Dictionary</th>
                    <th>Author</th>
                    <th>Entries</th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
    foreach ($public as $tome) {
        $owner = User::getById($tome->user_id);
        $entry_count = $tome->getEntryCount();
?>
                <tr style='vertical-align: middle;'>
                    <th scope='row'>
                        <div class='cell-container'><?=$tome->name?></div>
                    </th>
                    <td>
                        <div class='cell-container'>
                            <?= ($owner->display_name == null || $owner->display_name == '') ? "System" : $owner->display_name ?>
                        </div>
                    </td>
                    <td>
                        <div class='cell-container'><?=$entry_count?></div>
                    </td>
                    <td>
                        <div class='cell-container'><?= ($tome->is_writeable ? "Read / Write" : "Read-only") ?></div>
                    </td>
                    <td>
                        <div class='row'>
                            <div class='col-md-6'>
                                <a href='dictionary/subscribe/<?=$tome->id?>' title='Subscribe to dictionary'
                                    class='btn btn-md btn-success'><span class='bi bi-cloud-plus fs-5'
                                        role='subscribe'></span>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
    }
?>
            </tbody>
        </table>
    </div> <!-- CARD-BODY -->
</div> <!-- CARD -->
<br />
<div class='card text-bg-dark'>
    <div class='card-body'>
        <div class="d-grid gap-2 d-md-block">
            <h2 class='card-title'>My Dictionaries</h2>
        </div>
        <table class="table table-sm table-striped table-hover crossword-table" id="public-dictionaries-table">
            <thead>
                <tr>
                    <th>Dictionary</th>
                    <th>Author</th>
                    <th>Entries</th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
    foreach ($mine as $tome) {
        $owner = User::getById($tome->user_id);
        $entry_count = $tome->getEntryCount();
?>
                <tr style='vertical-align: middle;'>
                    <th scope='row'>
                        <div class='cell-container'><?=$tome->name?></div>
                    </th>
                    <td>
                        <div class='cell-container'>
                            <?= ($owner->display_name == null || $owner->display_name == '') ? "System" : $owner->display_name ?>
                        </div>
                    </td>
                    <td>
                        <div class='cell-container'><?=$entry_count?></div>
                    </td>
                    <td>
                        <div class='cell-container'><?= ($tome->is_writeable ? "Read / Write" : "Read-only") ?></div>
                    </td>
                    <td>
                        <div class='row'>
                            <div class='col-md-6'>
                                <a href='dictionary/subscribe/<?=$tome->id?>' title='Subscribe to dictionary'
                                    class='btn btn-md btn-success'><span class='bi bi-cloud-plus fs-5'
                                        role='subscribe'></span>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
    }
?>
            </tbody>
        </table>
    </div> <!-- CARD-BODY -->
</div> <!-- CARD -->