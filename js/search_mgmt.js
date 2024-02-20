if (typeof trackSearch === 'undefined') { trackSearch = {}; }

trackSearch.search_request_queue = null;
trackSearch.search_request_running = false;
trackSearch.extraRetrievals = 0;

trackSearch.proTips = new Array();
trackSearch.proTips.push("Pro tip! You can also search <strong>artist:adele</strong> or <strong>track:bohemian</strong>.");
trackSearch.proTips.push("Pro tip! You can paste in a Spotify share link if you need a specific track or version.");

trackSearch.reThe = /^the\s+/i; // Matches "the" at the start of a string, case-insensitive
trackSearch.reSpaces = /(?!^)\s+(?=\S)/g; // Matches any number of consecutive white spaces
trackSearch.reArtistSplit = /\s*\/\/\s*/; // Matches two forward-slashes with any leading/trailing whitespace
//trackSearch.reSpotifyLink = /^\s*https:\/\/open\.spotify\.com\/track\/([^?]+)/i; // Matches a link such as https://open.spotify.com/track/0riUhelZwZWyCPNt6qag6R?si=772e987d87a349f5 // Handled in PHP now

trackSearch.getProTip = function() {
    var i = Math.floor( Math.random()*trackSearch.proTips.length );
    return trackSearch.proTips[i];
}

trackSearch.build_search_request = function(txt) {
    var querystring = '';
    if (trackSearch.allow_title && trackSearch.allow_artist) {
        querystring = txt;//encodeURIComponent(txt);
    } else if (trackSearch.allow_title) {
        querystring = "track:"+txt;//encodeURIComponent("track:"+txt);
    } else if (trackSearch.allow_artist) {
        querystring = "artist:"+txt;//encodeURIComponent("artist:"+txt);
    } else {
        // Not sure what to do here!
        querystring = txt;//encodeURIComponent(txt);
    }
    if (!'limit' in trackSearch) { trackSearch.limit = 40; }
    return {
        query: querystring,
        resultType: 'track',
        market: trackSearch.market,
        limit: trackSearch.limit
    };
}

trackSearch.search_request = function(query,resultType,userMarket,resultLimit, resultOffset = 0) {
    trackSearch.ajaxOptions.data = {
        q: query,
        type: resultType,
        market: userMarket,
        limit: resultLimit,
        offset: resultOffset,
        playlist_id: trackSearch.playlist_id
    };
    $('#search_spinner').removeClass('hidden');
    $.ajax(root_path + '/ajax/proxy_search.php', trackSearch.ajaxOptions);
}

trackSearch.updateSearchBox = function(data, textStatus, jqXHR) {
    if ('updateSearchBoxCustom' in trackSearch) {
        trackSearch.updateSearchBoxCustom(data, textStatus, jqXHR); // Runs any custom actions for the page on which we're embedding
    }
}

trackSearch.processQueue = function() {
    trackSearch.search_request_running = false;
    $('#search_spinner').addClass('hidden');
    if (trackSearch.search_request_queue === null) {
        // No waiting request, so try retrieving more results
        if (trackSearch.extraRetrievals < 3) {
            var txt=$(trackSearch.inputBox).val();
            trackSearch.search_request_running = true;
            var req = trackSearch.build_search_request(txt);
            trackSearch.extraRetrievals++;
            var offset = trackSearch.limit * trackSearch.extraRetrievals;
            trackSearch.search_request(req.query,req.resultType,req.market,req.limit,offset);
        }
    } else {
        // There was another request waiting in the wings
        // Stash variables
        var _q  = trackSearch.search_request_queue.query;
        var _rT = trackSearch.search_request_queue.resultType;
        var _m  = trackSearch.search_request_queue.market;
        var _l  = trackSearch.search_request_queue.limit;
        // Clear queue
        trackSearch.search_request_queue = null;
        // Send queued request
        trackSearch.search_request(_q,_rT,_m,_l,0); // Always offset 0 for queued request
    }
}

trackSearch.handleTrackUpdate = function(jqXHR, textStatus) {
    // "success", "notmodified", "nocontent", "error", "timeout", "abort", or "parsererror"
    $("html,html *").css("cursor","auto"); // Reset cursor

    switch (textStatus) {
        case 'success':
            if ('handleTrackUpdateSuccessCustom' in trackSearch) {
                trackSearch.handleTrackUpdateSuccessCustom(); // Runs any custom actions for the page on which we're embedding
            }
            break;
        case 'error':
            if ('handleTrackUpdateErrorCustom' in trackSearch) {
                trackSearch.handleTrackUpdateErrorCustom(); // Runs any custom actions for the page on which we're embedding
            } else {
                alert("Error saving selection. Please try again.");
            }
            break;
        case 'timeout':
            if ('handleTrackUpdateTimeoutCustom' in trackSearch) {
                trackSearch.handleTrackUpdateTimeoutCustom(); // Runs any custom actions for the page on which we're embedding
            } else {
                alert("The server did not respond in time. Please try again.");
            }
            break;
        default:
            break;
    }
}

trackSearch.ajaxOptions = {
    async: true,
    cache: false,
    success: trackSearch.updateSearchBox,
    complete: trackSearch.processQueue,
    dataType: 'json',
    method: 'GET',
    timeout: 10000,
    headers: {
        Authorization: 'Bearer '+trackSearch.token,
        'Content-type': 'application/json'
    }
};
trackSearch.clearAjaxOptions = {
    async: true,
    cache: false,
    complete: trackSearch.handleTrackUpdate,
    method: 'GET',
    timeout: 10000
}

trackSearch.checkTrack = function(trackName, artistName) {
    // Checks if this track is valid, using the relevant options
    // Return true at the first sign of success

    if (!trackSearch.strict_mode) { return true; } // Unless we're in strict mode, allow all tracks

    // Create arrays of the variants to check - empty if that field isn't valid for search
    var tracks = new Array();
    if (trackSearch.allow_title) {
        // We can validate on title
        trackName = trackName.toUpperCase(); // Match in uppercase, matching trackSearch.search_letter
        // Always add the full track title
        tracks.push(trackName);
        if (trackSearch.the_agnostic) {
            tracks.push(trackName.replace(trackSearch.reThe,'')); // Also include a "no the" variant if appropriate
        }
    }
    // Now check them
    for (var i in tracks) {
        if (tracks[i].substr(0,1) == trackSearch.search_letter) {return true;}
    }

    // Repeat process for artists - but they can be comma-separated
    var artists = new Array();
    if (trackSearch.allow_artist) {
        // Split artist names by separator (//)
        var splitArtists = artistName.toUpperCase().split(this.reArtistSplit); // Match in uppercase, matching trackSearch.search_letter
        for (var i in splitArtists) {
            var thisArtist = splitArtists[i].trim();
            // Always include full artist
            artists.push(thisArtist);
            if (trackSearch.the_agnostic) {
                // Also include a "no the" variant if appropriate
                artists.push(thisArtist.replace(trackSearch.reThe,''));
            }
            // Now check for [firstname] [surname] pattern
            if (!thisArtist.match(trackSearch.reThe)) {
                if ((thisArtist.match(trackSearch.reSpaces) || []).length == 1) {
                    // If exactly one whitespace divider, and no "the" then assume [firstname] [surname] and add [surname] as entry
                    //console.log("Parsing: "+thisArtist);
                    //console.log("Adding: "+thisArtist.split(trackSearch.reSpaces)[1]);
                    artists.push(thisArtist.split(trackSearch.reSpaces)[1]);
                }
            }
        }
    }
    // Now check them
    for (var i in artists) {
        if (artists[i].substr(0,1) == trackSearch.search_letter) {return true;}
    }

    // If we got here, there's no valid matches
    return false;
}

trackSearch.validateTracks = async function(pattern) {
    $(pattern).each( function() {
        var link = $(this).children('a').first();
        if (link.data('track-title') === undefined) { link.data('track-title',''); }
        if (link.data('track-artists') === undefined) { link.data('track-artists',''); }
        if (trackSearch.checkTrack( 
                decodeURIComponent( link.data('track-title') ), 
                decodeURIComponent( link.data('track-artists') ) 
        )) {
            // Valid choice
            $(this).addClass('valid').removeClass('invalid').removeClass('validating');
        } else {
            // Invalid choice
            $(this).addClass('invalid').removeClass('valid').removeClass('validating');
        }
    } );
    var hiddenCount = $('#search-results-container li.invalid').length;
    if (hiddenCount==0) {
        $('#hidden-results-count-container').addClass('visually-hidden');
        $('#hidden-results-count').text('0');
        $('#must-begin-with').text($('#beginning-with-letter').text());
    } else {
        $('#hidden-results-count-container').removeClass('visually-hidden');
        $('#hidden-results-count').text(hiddenCount.toString());
        $('#must-begin-with').text($('#beginning-with-letter').text());
    }
}

trackSearch.clearLetter = function(letter_id) {
    $("html,html *").css("cursor","wait"); // Wait cursor
    $.ajax(root_path + '/ajax/clear_track.php?id='+letter_id.toString(), trackSearch.clearAjaxOptions);
}

trackSearch.init = function(inputBox, outputBox, limit=40) {
    trackSearch.limit = limit;
    trackSearch.inputBox = inputBox;
    trackSearch.outputBox = outputBox;
    $(document).ready(function() {
        // Handle typing in search box
        $(inputBox).on('keyup',function(event) {
            // Only deal with actual characters
            /*if (((event.which < 40)||(event.which > 90)) && (event.which != 8)) {
                return;
            }*/
            // Don't run loads of simultaneous queries
            var txt=$(this).val();
            if (trackSearch.search_request_running) {
                // Set or overwrite queued request, ready for completion of current one
                trackSearch.search_request_queue = trackSearch.build_search_request(txt);
            } else {
                if (txt.length > 3) {
                    trackSearch.search_request_running = true;
                    trackSearch.extraRetrievals = 0;
                    var req = trackSearch.build_search_request(txt);
                    trackSearch.search_request(req.query,req.resultType,req.market,req.limit,0);
                }
            }
        });

        // Handle clicking on a search result
        $(outputBox).on('click','li.valid a.search-result',function(){
            $("html,html *").css("cursor","wait"); // Set wait cursor

            var ele = $(this);

            // Pass the request to save the track to the playlist
            requestData = new URLSearchParams({
                'id':               letter_id,                
                'spotify_id':       ele.data('track-id'),
                'cached_title':     decodeURIComponent(ele.data('track-title')),
                'cached_artist':    decodeURIComponent(ele.data('track-artists'))
            });
            var ajaxUpdateOptions = {
                async: true,
                cache: false,
                complete: trackSearch.handleTrackUpdate,
                dataType: 'json',
                method: 'GET',
                timeout: 8000
            };
            $.ajax(root_path+"/ajax/assign_track.php?"+requestData.toString(), ajaxUpdateOptions);
            

            if ('handleSearchClickCustom' in trackSearch) {
                trackSearch.handleSearchClickCustom(ele); // Runs any custom actions for the page on which we're embedding
            }
        });

        $('table').on('click','a.clear-track', function() {
            var lid = $(this).data('letter-id');
            if ((lid === undefined) || (lid == '')) {
                $(this).remove();
            } else {
                trackSearch.clearLetter(lid);
            }
        })

        // Populate search pro-tip
        $('#search-protip').html(trackSearch.getProTip());
    });
}