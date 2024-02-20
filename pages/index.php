<?php

$error_messages = [];
if (isset($_REQUEST['error_message'])) {
    $error_messages[] = $_REQUEST['error_message'];
}

$user = $_SESSION['USER'];

if (isset($_REQUEST['newname'])) {
    $user->display_name = $_REQUEST['newname'];
    $user->save();
}

echo <<<END_SCRIPTS
<!-- Set variable -->
<script type='text/javascript'>
if (typeof(root_path) === 'undefined') { var root_path = "{$config['root_path']}"; }
</script>
<!-- Include playlist-delete script -->
<script src='js/delete_handler.js'></script>
<!-- Include leave-playlist script -->
<script type='text/javascript' src='{$config['root_path']}/js/leave_handler.js'></script>
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
// List all playlists
$criteriaMine = ['user_id','=',$_SESSION['USER_ID']];
$my_playlists = Playlist::find($criteriaMine);
// TODO - list playlists which they have (actively) joined
$my_participations = Participation::find($criteriaMine);
$joined_playlist_ids = [];
foreach ($my_participations as $part) {
    $joined_playlist_ids[$part->playlist_id] = true;
}
$criteriaJoined = [['id','IN',array_keys($joined_playlist_ids)],];
$joined_playlists = Playlist::find($criteriaJoined);

if (count($my_playlists)==0) {
    // No playlists of our own
?>

<h2 class='card-title'>Your Playlists</h2>
<div class="row">
    <div class="col-12">
        <h3>You don't have any playlists. How sad!</h3>
        <h4>Click below to create one.</h4>
        <a class="btn btn-primary" href="playlist/create">Create</a>
    </div>
</div>
<?php
} else {
    // At least one playlist of our own
?>
<h2 class='card-title'>Your Playlists <a class="btn btn-primary mb-1" href="<?= $config['root_path'] ?>/playlist/create">+ New</a></h2>

<table class="table table-sm table-striped table-hover playlist-table" id="my-playlists-table">
    <thead>
        <tr>
            <th>Playlist</th>
            <th>Destination</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach ($my_playlists as $playlist) {
?>
        <tr style='vertical-align: middle;'>
        <th scope='row'><div class='cell-container'><?=$playlist->display_name?></div></th>
        <td><div class='cell-container'><?=$playlist->destination?></div></td>
        <td>
            <div class='row'>
                <div class='col-md-6'>
                    <a href='playlist/manage/<?=$playlist->id?>' title='View playlist' class='btn btn-md btn-success'><span class='bi bi-eye'></span></a>
                    <a href='playlist/share/<?=$playlist->id?>' title='Share playlist' class='btn btn-md btn-primary'><span class='bi bi-share'></span></a>
                </div>
                <div class='col-md-6'>
                    <a href='playlist/edit/<?=$playlist->id?>' title='Edit playlist' class='btn btn-md btn-warning'><span class='bi bi-pencil-square' role='edit'></span></a>
                    <a href='#' class='btn btn-md btn-danger' title='Delete playlist' data-bs-toggle='modal' data-bs-target='#playlistDeleteModal' role='delete' onclick='deleteHandler.idToDelete = <?=$playlist->id?>;'><span class='bi bi-trash3'></span></a>
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
<br /><hr /><br />
<div class='card text-bg-dark'>
<div class='card-body'>
<h2 class='card-title'>Joined Playlists</h2>
<?php
if (count($joined_playlists)==0) {
    ?>
    
    <div class="row">
        <div class="col-12">
            <h4>You haven't joined any playlists.</h4>
        </div>
    </div>
    <?php
    } else {
    ?>
    <table class="table table-sm table-striped table-hover playlist-table" id="joined-playlists-table">
        <thead>
            <tr>
                <th>Playlist</th>
                <th>Destination</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    <?php
        foreach ($joined_playlists as $playlist) {
            echo "<tr style='vertical-align: middle;'>\n";
            echo "<th scope='row'><div class='cell-container'>{$playlist->display_name}</div></th>\n";
            echo "<td><div class='cell-container'>{$playlist->destination}</div></td>\n";
            echo "<td>";
            echo "<a href='playlist/join/".$playlist->getShareCode()."' title='View playlist' class='btn btn-md btn-success m-2'><span class='bi bi-eye'></span></a>";
            echo "<a href='#' class='btn btn-md btn-danger m-2 leave-playlist' title='Leave playlist' id='leave-playlist-{$playlist->id}' onclick='leaveHandler.idToLeave= {$playlist->id};'><span class='bi bi-node-minus'></span></a>";
            echo "</td>\n";
            echo "</tr>\n";
        }
    ?>
        </tbody>
    </table>
    <?php
    }
?>
</div> <!-- CARD-BODY -->
</div> <!-- CARD -->

<div class="modal fade" id="playlistDeleteModal" tabindex="-1">
  <div class="modal-dialog .modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Playlist</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="deleteModalCloseX"></button>
      </div>
      <div class="modal-body">
      <div class="row">
        <div class="col-12">
            Where would you like to delete the playlist from?
        </div>
      </div>
      <div class="row">
        <div class="col-4-md p-2">
            <a class='btn btn-md btn-warning' id='deleteHere' style='width: 100%;'>Just here</a>
        </div>
        <div class="col-4-md p-2">
            <a class='btn btn-md btn-danger' id='deleteBoth' style='width: 100%;'>Here and on Spotify</a>
        </div>
        <div class="col-4-md p-2">
            <button class='btn btn-md btn-success' id='deleteCancel' data-bs-dismiss="modal" style='width: 100%;'>Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="playlistLeaveModal" tabindex="-1">
  <div class="modal-dialog .modal-fullscreen-lg-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Leave Playlist</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="leaveModalCloseX"></button>
      </div>
      <div class="modal-body">
      <div class="row">
        <div class="col-12">
            <div>Leave this playlist?</div>
            <div class='fs-6 fw-lighter fst-italic'>You won't see it in Spotify any more.</div>
        </div>
      </div>
      <div class="row">
        <div class="col-6-md p-2">
            <a class='btn btn-md btn-danger' id='leavePlaylist' style='width: 100%;'>Leave</a>
        </div>
        <div class="col-6-md p-2">
            <button class='btn btn-md btn-success' id='leaveCancel' data-bs-dismiss="modal" style='width: 100%;'>Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>