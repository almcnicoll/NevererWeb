<?php
    use Dictionaries\Tome;
    use Dictionaries\TomeEntry;
    use Security\User;
    use Basic\db;
    use Dictionaries\Subscription;

    // Deal with any messages from request
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

    // Ensure subscriptions are up-to-date
    $sql = "CALL CreateMissingSubscriptions();";
    $pdo = db::getPDO();
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();

    // Retrieve list of Tomes
    $readable_tomes = Tome::getAllForUser($_SESSION['USER_ID']);
    $mine = array_filter($readable_tomes, fn($t) => $t->user_id == $_SESSION['USER_ID']);
    $public = array_filter($readable_tomes, fn($t) => $t->user_id != $_SESSION['USER_ID']);
    $subs = Subscription::getAllForUser($_SESSION['USER_ID']);
    $subs = array_column($subs, null, 'tome_id'); // Convert it to an associative array with tome_id as the key
?>
<h2>Dictionary Management</h2>

<!-- MY DICTIONARIES -->
<div class='card text-bg-dark'>
    <div class='card-body'>
        <div class="d-grid gap-2 d-md-block">
            <h2 class='card-title'>My Dictionaries
                <a class="btn btn-primary mb-1 col-1" href="<?= $config['root_path'] ?>/dictionary/create">+ New</a>
            </h2>
        </div>
        <?php        
        if (count($mine) == 0) {
        ?>
        <div class="row">
            <div class="col-12">
                <h3>You don't have any dictionaries. How sad!</h3>
                <h4>Click below to create one.</h4>
                <a class="btn btn-primary" href="<?= $config['root_path'] ?>/dictionary/create">Create</a>
            </div>
        </div>
        <?php
        } else {
        ?>
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
                        $subscribed = array_key_exists($tome->id, $subs) && $subs[$tome->id]->subscribed==1;
                ?>
                <tr style='vertical-align: middle;' class='tome-row <?= ($subscribed?"subscribed":"not-subscribed") ?>'>
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
                        <div class='cell-container'><?= ($tome->writeable ? "Read / Write" : "Read-only") ?></div>
                    </td>
                    <td>
                        <div class='row'>
                            <div class='col-md-4'>
                                <?php if ($subscribed) {
                                    ?>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/subscribe/<?=$tome->id?>'
                                    title='Subscribe to dictionary' class='btn btn-md btn-outline-success subscribe'
                                    style="display:none;">
                                    <span class='bi bi-cloud-plus fs-5 text-dark' role='subscribe'></span>
                                </a>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/unsubscribe/<?=$tome->id?>'
                                    title='Unsubscribe from dictionary'
                                    class='btn btn-md btn-outline-secondary unsubscribe'>
                                    <span class='bi bi-cloud-plus-fill fs-5 text-success' role='unsubscribe'></span>
                                </a>
                                <?php
                                } else {
                                    ?>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/subscribe/<?=$tome->id?>'
                                    title='Subscribe to dictionary' class='btn btn-md btn-outline-success subscribe'>
                                    <span class='bi bi-cloud-plus fs-5 text-dark' role='subscribe'></span>
                                </a>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/unsubscribe/<?=$tome->id?>'
                                    title='Unsubscribe from dictionary'
                                    class='btn btn-md btn-outline-secondary unsubscribe' style="display:none;">
                                    <span class='bi bi-cloud-plus-fill fs-5 text-success' role='unsubscribe'></span>
                                </a>
                                <?php
                                }
                                ?>
                            </div>
                            <div class='col-md-4'>
                                <a href="<?= $config['root_path'] ?>/dictionary/edit/<?=$tome->id?>"
                                    title="Edit dictionary" class="btn btn-md btn-outline-warning edit">
                                    <span class="bi bi-pencil fs-5 text-dark"></span>
                                </a>
                            </div>
                            <div class='col-md-4'>
                                <a href="<?= $config['root_path'] ?>/dictionary/delete/<?=$tome->id?>"
                                    title="Delete dictionary" class="btn btn-md btn-outline-danger delete"
                                    data-id="<?= $tome->id ?>" data-bs-toggle='modal' data-bs-target='#tomeDeleteModal'
                                    role='delete'>
                                    <span class=" bi bi-trash fs-5 text-dark"></span>
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
        <?php
        }
        ?>
    </div> <!-- CARD-BODY -->
</div> <!-- CARD -->
<br />
<!-- PUBLIC DICTIONARIES -->
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
                        $subscribed = array_key_exists($tome->id, $subs) && $subs[$tome->id]->subscribed==1;
                ?>
                <tr style='vertical-align: middle;' class='tome-row <?= ($subscribed?"subscribed":"not-subscribed") ?>'>
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
                        <div class='cell-container'><?= ($tome->writeable ? "Read / Write" : "Read-only") ?></div>
                    </td>
                    <td>
                        <div class='row'>
                            <div class='col-md-6'>
                                <?php if ($subscribed) {
                                    ?>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/subscribe/<?=$tome->id?>'
                                    title='Subscribe to dictionary' class='btn btn-md btn-outline-success subscribe'
                                    style="display:none;">
                                    <span class='bi bi-cloud-plus fs-5 text-dark' role='subscribe'></span>
                                </a>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/unsubscribe/<?=$tome->id?>'
                                    title='Unsubscribe from dictionary'
                                    class='btn btn-md btn-outline-secondary unsubscribe'>
                                    <span class='bi bi-cloud-plus-fill fs-5 text-success' role='unsubscribe'></span>
                                </a>
                                <?php
                                } else {
                                    ?>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/subscribe/<?=$tome->id?>'
                                    title='Subscribe to dictionary' class='btn btn-md btn-outline-success subscribe'>
                                    <span class='bi bi-cloud-plus fs-5 text-dark' role='subscribe'></span>
                                </a>
                                <a href='<?= $config['root_path'] ?>/dictionary/*/unsubscribe/<?=$tome->id?>'
                                    title='Unsubscribe from dictionary'
                                    class='btn btn-md btn-outline-secondary unsubscribe' style="display:none;">
                                    <span class='bi bi-cloud-plus-fill fs-5 text-success' role='unsubscribe'></span>
                                </a>
                                <?php
                                }
                                ?>
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

<div class="modal fade" id="tomeDeleteModal" tabindex="-1">
    <div class="modal-dialog .modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Dictionary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    id="deleteModalCloseX"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        Are you sure you want to delete the dictionary?
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 p-2">
                        <a class='btn btn-md btn-danger' id='deleteConfirm' style='width: 100%;'>Yes</a>
                    </div>
                    <div class="col-md-6 p-2">
                        <button class='btn btn-md btn-success' id='deleteCancel' data-bs-dismiss="modal"
                            style='width: 100%;'>Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>