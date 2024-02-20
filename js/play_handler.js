if (typeof playHandler === 'undefined') { var playHandler = {}; }

playHandler.getIcon = function(device) {
    switch(device.type.toLowerCase()) {
        case 'computer':
            return 'laptop';
            break;
        case 'smartphone':
            return 'phone';
            break;
        case 'speaker':
            return 'speaker';
            break;
        default:
            return 'question-diamond';
            break;
    }
}

playHandler.reportDevices = function(data, textStatus, jqXHR) {
    // Handle callback
    var containerElement = $(playHandler.container);
    containerElement.css("cursor","auto").html(''); // Put cursor back, remove loading spinner
    var icon = '';
    var device;
    if ('devices' in data) {
        if (data.devices.length == 0) {
            // No devices to play on - say this
            containerElement.html("<span class='bi bi-emoji-frown'></span>&nbsp;Cannot detect a Spotify-ready device.<br />"
                                    +"<a href='https://open.spotify.com/playlist/"+playHandler.spotify_playlist_id+"' target='_blank'>Click here to open Spotify</a>");
        } else if (data.devices.length == 1) {
            // Only one device - announce that we'll play on that
            device = data.devices[0];
            icon = playHandler.getIcon(device);
            containerElement.html("<span class='bi bi-"+icon+"'></span>&nbsp;Playing on <a class='play-on-device' href='#' data-device-id='"+device.id+"'>"+device.name+"</a>.");
            // And actually play...
            $('a.play-on-device').trigger('click');
        } else {
            // Populate devices
            var deviceHtmls = new Array();
            for (var i in data.devices) {
                // alert
                device = data.devices[i];
                var icon = playHandler.getIcon(device);
                deviceHtmls.push("<li><span class='bi bi-"+icon+"'></span>&nbsp;<a class='play-on-device' href='#' data-device-id='"+device.id+"'>"
                                +((device.is_active)?"<strong>"+device.name+"</strong>":device.name)+"</a></li>");
                containerElement.html("<button class='btn btn-primary dropdown-toggle' type='button' data-bs-toggle='dropdown' aria-expanded='false'>"
                                            +"Play on device"
                                            +"</button>\n"
                                            +"<ul class='dropdown-menu'>\n"
                                            +deviceHtmls.join("\n")
                                            +"</ul>");
            }
        }
    }
}

playHandler.getDevices = function() {    
    playHandler.url = root_path+"/ajax/get_devices.php";
    playHandler.ajaxOptions = {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'POST',
        timeout: 10000,
        success: playHandler.reportDevices
    };
    // Wait cursor, loading message
    $(playHandler.container).css("cursor","wait").append("<div class='spinner-border spinner-border-sm text-primary hidden' id='devices_spinner' role='status'><span class='visually-hidden'>Loading...</span></div>");
    $.ajax(playHandler.url, playHandler.ajaxOptions);
}

playHandler.playOnDevice = function() {
    var device_id = $(this).data('device-id');
    playHandler.playUrl = root_path+"/ajax/play_on_device.php";
    playHandler.playAjaxOptions = {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'POST',
        timeout: 10000,
        data: {
            playlist_id: playHandler.playlist_id,
            device_id: device_id
        }
    };
    $.ajax(playHandler.playUrl, playHandler.playAjaxOptions);
}

playHandler.init = function(container) {
    $(document).ready( function() {
        playHandler.container = container;
        $(playHandler.container).on('click','a.play-on-device',playHandler.playOnDevice);
    } );
}