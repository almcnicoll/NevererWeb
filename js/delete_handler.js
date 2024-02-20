var deleteHandler = {};
deleteHandler.idToDelete = null;

deleteHandler.reportOnDelete = function(jqXHR, textStatus) {
    // Handle callback
    $("html,html *").css("cursor","auto"); // Put cursor back
    $('#deleteModalCloseX').trigger('click'); // Close modal
    top.location.reload(); // Reload page
}

deleteHandler.deletePlaylist = function(local, fromSpotify) {
    // Check we've specified an id
    if (deleteHandler.idToDelete == null) {
        alert("Could not delete playlist. Please try again later.");
        return;
    }
    deleteHandler.url = root_path+"/ajax/delete_playlist.php?playlist_id="+deleteHandler.idToDelete;
    deleteHandler.ajaxOptions = {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'POST',
        timeout: 10000,
        complete: deleteHandler.reportOnDelete,
        data: {
            deleteLocal: local,
            deleteFromSpotify: fromSpotify
        }
    };
    $("html, html *").css("cursor","wait"); // Wait cursor
    $.ajax(deleteHandler.url, deleteHandler.ajaxOptions);
}

$(document).ready( function() {
    $('#deleteHere').click( function() { deleteHandler.deletePlaylist(true,false); } );
    $('#deleteBoth').click( function() { deleteHandler.deletePlaylist(true,true); } );
} );