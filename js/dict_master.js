/**
 * Main thread controller for dictionary sync.
 *
 * - Instantiates and communicates with the `dict_worker.js` WebWorker.
 * - Listens for 'fetch' messages from the worker and performs AJAX using `makeAjaxCall()`.
 * - Sends the result back to the worker using postMessage.
 */
// TODO - HIGH consider subscription status when synchronising / serving dictionaries
// Super-variable!
let dictionary = {};

// #region WORKER SETUP
/** @type {string} Relative path to the WebWorker script. '~ROOT~' is substituted in index.php. */
dictionary.worker_path = "~ROOT~/js/dict_worker.js";

/** @type {Worker} WebWorker instance for background sync and IndexedDB operations. */
dictionary.worker = new Worker(dictionary.worker_path);

/** @type {boolean} Tracks whether the dictionary-entry sync has completed */
dictionary.entries_sync_complete = false;
/** @type {boolean} Tracks whether the dictionary-clue sync has completed */
dictionary.clues_sync_complete = false;
// #endregion

// #region INIT
dictionary.initialisationSetup = {};
/**
 * Manages multiple initialisation routines which finish
 * asynchronously
 * @param {Array<string>} parts the names of the initialisation routines to trigger and await
 * @param {function() : void} callback the function to call when all initialisations are complete
 */
dictionary.multiPartInit = function (parts, callback) {
    // Set up parts
    dictionary.initialisationSetup.parts = {};
    for (var i in parts) {
        var partName = parts[i];
        dictionary.initialisationSetup.parts[partName] = {
            name: partName,
            complete: false,
        };
    }
    // Set up callback - called when worker returns "initialised"
    dictionary.initialisationSetup.callback = callback;
    // Trigger parts
    for (var i in dictionary.initialisationSetup.parts) {
        var partName = dictionary.initialisationSetup.parts[i].name;
        console.log("Initialisation: " + partName);
        dictionary.worker.postMessage({
            type: "init." + partName,
            root_path: root_path,
        });
    }
};
/**
 * Handles the worker thread saying that it's finished initialising
 * part of what's needed. When all parts are complete, we
 * fire off the "continueSyncEntries" action
 * @param {string} partName the init part that is returning
 * @param {object} msg the message object returned from the worker, in case we need debug info
 */
dictionary.initReturn = function (partName, msg) {
    // Retrieve the relevant part
    var part = dictionary.initialisationSetup.parts[partName];
    console.log("Initialised: " + partName);
    part.complete = true;
    // See whether all partial initialisations have completed
    var incomplete = Object.values(dictionary.initialisationSetup.parts).filter((p) => p.complete === false).length;
    if (incomplete === 0) {
        // We're ready to start the actual sync
        dictionary.worker.postMessage({
            type: "continueSyncEntries",
        });
    }
};
// #endregion

// #region FETCH FUNCTIONS
dictionary.launchFetch = function (msg) {
    const url = msg.data.url;
    // Make the AJAX request
    makeAjaxCall(
        msg.method,
        "~ROOT~/" + url + "?domain=ajax",
        msg.data,
        dictionary.fetchSuccess.bind(null, msg.id),
        dictionary.fetchFailure.bind(null, msg.id)
    );
};
dictionary.fetchSuccess = function (msgId, response) {
    // Success callback
    dictionary.worker.postMessage({
        type: "fetched",
        id: msgId,
        success: true,
        payload: response,
    });
};
dictionary.fetchFailure = function (msgId, error) {
    // Failure callback
    dictionary.worker.postMessage({
        type: "fetched",
        id: msgId,
        success: false,
        payload: error,
    });
};
// #endregion

// #region WORDLIST FUNCTIONS
/**
 * Populates the suggested word list with the returned matches in the specified format
 * @param {Array<Object>} matches an array of matched word entries
 * @param {int} totalMatches the total number of matches (in case we've paged the results) - currently unused
 * @param {string} destination the jQuery identifier of the location to put the results
 * @param {string} format the format in which to put the results (currently table-row|text)
 */
dictionary.populateSuggestedWords = function (matches, totalMatches, destination, format) {
    /** @type {string} */
    var output = "";
    switch (format) {
        case "table-row":
            if (matches.results.length == 0) {
                output = "<h5>No matches</h5>";
            } else {
                output =
                    "<tr><td class='suggested-word-list-item'>" +
                    matches.results.map((o) => o.word).join("</td></tr>\n<tr><td class='suggested-word-list-item'>") +
                    "</td></tr>";
            }
            break;
        case "text":
            output = matches.results.map((o) => o.bare_letters).join("\n");
            break;
        case "default":
            throw new Exception("Invalid format " + format + " specified.");
    }

    $(destination).html(output); // Works fine if it's plain text too
    // Set each word <td> to have a matching data-word property
    $("td.suggested-word-list-item").each(function () {
        $(this).data("word", $(this).text().toLowerCase()).attr("data-word", $(this).text().toLowerCase());
    });
};
dictionary.populateSuggestedClues = function (matches) {
    for (var i in matches) {
        let result = matches[i];
        // TODO - make this better, add support for multiple clues against a single word
        $(`td.suggested-word-list-item[data-word=${result.word}]`)
            .addClass("has-tome-clue")
            .attr("title", result.question);
    }
};

/**
 * Takes a pattern in the form A?B??C?D and returns a regular expression for searching a word list
 * @param {string} pattern the pattern to convert
 * @param {boolean} bareLettersVersion should Regex be suitable for searching a bare-letters list, or one with spaces and punctuation?
 * @returns RegExp the regular expression used to search
 */
dictionary.getRegexFromPattern = function (pattern, bareLettersVersion = true) {
    if (bareLettersVersion) {
        // This is the simple one
        var rePattern = pattern.replaceAll("?", ".").toUpperCase();
        return (re = new RegExp(rePattern, "i"));
    } else {
        // This is the complex one
        var rePattern = pattern.replaceAll("?", ".").toUpperCase();
        var arr = rePattern.split("");
        rePattern = arr.join("[\\s'-]*");
        return (re = new RegExp(rePattern, "i"));
    }
};

/**
 * Requests a list of words from the worker whose `word` property matches a given regex.
 *
 * @param {RegExp} regex - Regular expression to match against the 'word' field.
 * @param {string} destination - Where to output the results (a jQuery query string)
 * @param {string} format - What form to post the results (currently only table-row or text)
 * @returns void
 */
dictionary.lookupWordsByRegex = function (regex, length, destination, format) {
    dictionary.worker.postMessage({
        type: "lookupByRegex",
        regex: regex.source,
        flags: regex.flags,
        length: length,
        destination: destination,
        format: format,
    });
};

/**
 * Requests a list of words from the worker whose `word` property matches a given regex.
 *
 * @param {RegExp} regex - Regular expression to match against the 'word' field.
 * @param {string} destination - Where to output the results (a jQuery query string)
 * @param {string} format - What form to post the results (currently only table-row or text)
 * @returns void
 */
dictionary.lookupWordsByPattern = function (pattern, length, destination, format) {
    dictionary.worker.postMessage({
        type: "lookupByPattern",
        pattern: pattern.toUpperCase(),
        length: length,
        destination: destination,
        format: format,
    });
    // dict_Worker.js returns message "regexResults" when complete
};
// #endregion

// #region MESSAGE LOOP
/** If the worker requests a fetch, this makes an AJAX call and sends the result back.
 *
 * @param {MessageEvent} e - The event object containing the message from the worker.
 */
dictionary.worker.onmessage = function (e) {
    const msg = e.data;
    switch (msg.type) {
        case "initialised":
            // Get subtype and send it to target function
            dictionary.initReturn(msg.subType, msg);
            break;
        case "fetch":
            dictionary.launchFetch(msg);
            break;
        case "regexResults":
            dictionary.populateSuggestedWords(msg.results, msg.totalMatches, msg.destination, msg.format);
            break;
        case "tomeClueResults":
            dictionary.populateSuggestedClues(msg.results);
            break;
        case "syncIncompleteEntries":
            // Update the UI
            $("#status-bar").html(
                "Synchronised " +
                    msg.count +
                    " words <i class='bi bi-info-square-fill' title='Synchronising the full dictionary happens once per browser and will take several minutes to an hour.'></i>"
            );
            // Now trigger the next partial sync
            dictionary.worker.postMessage({
                type: "continueSyncEntries",
                root_path: root_path,
            });
            break;
        case "syncCompleteEntries":
            // Update the UI
            $("#status-bar").html("Dictionary-entry sync complete");
            dictionary.entries_sync_complete = true;
            // Now trigger the clue sync process
            dictionary.worker.postMessage({
                type: "continueSyncClues",
                root_path: root_path,
            });
            break;
        case "syncIncompleteClues":
            // Update the UI
            $("#status-bar").html(
                "Synchronised " +
                    msg.count +
                    " clues <i class='bi bi-info-square-fill' title='Synchronising the full clue list happens once per browser but should be fairly speedy.'></i>"
            );
            // Now trigger the next partial sync
            dictionary.worker.postMessage({
                type: "continueSyncClues",
                root_path: root_path,
            });
            break;
        case "syncCompleteClues":
            // Update the UI
            $("#status-bar").html("Full dictionary sync complete");
            dictionary.clues_sync_complete = true;
            break;
        default:
            console.log("Unexpected message " + msg.type + " sent to dict_master.js");
            break;
    }
};
// #endregion

// #region DOCUMENT READY
/**
 * Initiates the sync process when the DOM is ready.
 * Fires off a number of init.x messages, prompting
 * a bunch of initialisation routines.
 * Sync commences properly when they are all complete
 */
$(document).ready(function () {
    dictionary.multiPartInit(["tomeList", "syncMetadata"], dictionary.initReturn);
});
// #endregion
