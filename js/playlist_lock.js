if (typeof listLocker === 'undefined') { listLocker = {}; }    

listLocker.url = root_path+"/ajax/lock_playlist.php?playlist_id="+playlist_id+"&action=";

listLocker.updateButtonNow = function() {
    // Refresh immediately
    $('#btn-lock-list').toggle();
    $('#btn-unlock-list').toggle();
    $("html, html *").css("cursor","auto");
}

listLocker.ajaxOptions = {
    async: true,
    cache: false,
    dataType: 'json',
    method: 'GET',
    timeout: 4000,
    complete: listLocker.updateButtonNow
};

listLocker.init = function() {
    listLocker.lockButton = '#btn-lock-list';
    listLocker.unlockButton = '#btn-unlock-list';
    $(document).ready(
        function () {
            // Lock button
            if (listLocker.lockButton!=null) {
                $(listLocker.lockButton).on('click',function() {
                    $(listLocker.lockButton).prop('disabled',true);
                    $("html, html *").css("cursor","wait");
                    $.ajax(listLocker.url+'lock', listLocker.ajaxOptions);
                });
            }
            // Unlock button
            if (listLocker.unlockButton!=null) {
                $(listLocker.unlockButton).on('click',function() {
                    $(listLocker.unlockButton).prop('disabled',true);
                    $("html, html *").css("cursor","wait");
                    $.ajax(listLocker.url+'unlock', listLocker.ajaxOptions);
                });
            }
        }
    );
}