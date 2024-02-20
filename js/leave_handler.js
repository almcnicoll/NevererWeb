var leaveHandler = {};
leaveHandler.idToDelete = null;

leaveHandler.reportOnLeave = function(jqXHR, textStatus) {
    // Handle callback
    $("html,html *").css("cursor","auto"); // Put cursor back
    $('#leaveModalCloseX').trigger('click'); // Close modal
    top.location.reload(); // Reload page
}

leaveHandler.leavePlaylist = function() {
    // Check we've specified an id
    if (leaveHandler.idToLeave == null) {
        alert("Could not leave playlist. Please try again later.");
        return;
    }
    leaveHandler.url = root_path+"/ajax/leave_playlist.php?playlist_id="+leaveHandler.idToLeave;
    leaveHandler.ajaxOptions = {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'POST',
        timeout: 10000,
        complete: leaveHandler.reportOnLeave
    };
    $("html, html *").css("cursor","wait"); // Wait cursor
    $.ajax(leaveHandler.url, leaveHandler.ajaxOptions);
}

$(document).ready( function() {
    $('.leave-playlist').click( leaveHandler.leavePlaylist );
} );