// File: js/dict_master.js

/**
 * Main thread controller for dictionary sync.
 * 
 * - Instantiates and communicates with the `dict_worker.js` WebWorker.
 * - Listens for 'fetch' messages from the worker and performs AJAX using `makeAjaxCall()`.
 * - Sends the result back to the worker using postMessage.
 */

/** @type {string} Relative path to the WebWorker script. '~ROOT~' is substituted in index.php. */
var worker_path = "~ROOT~/js/dict_worker.js";

/** @type {Worker} WebWorker instance for background sync and IndexedDB operations. */
const worker = new Worker(worker_path);

/**
 * Handles messages received from the worker thread.
 * If the worker requests a fetch, this makes an AJAX call and sends the result back.
 * 
 * @param {MessageEvent} e - The event object containing the message from the worker.
 */
worker.onmessage = function (e) {
    const msg = e.data;

    // If the worker is asking for a fetch operation
    if (msg.type === "fetch") {
        const url = msg.data.url;

        // Make the AJAX request
        makeAjaxCall(
            msg.method,                                // HTTP method (e.g., 'get')
            '~ROOT~/' + url + '?domain=ajax',          // URL with domain=ajax param appended
            msg.data,                                  // Any additional request data
            function (response) {                      // Success callback
                worker.postMessage({
                    type: "fetched",
                    id: msg.id,
                    success: true,
                    payload: response
                });
            },
            function (error) {                         // Failure callback
                worker.postMessage({
                    type: "fetched",
                    id: msg.id,
                    success: false,
                    payload: error
                });
            }
        );
    }
};

/**
 * Requests a list of words from the worker whose `word` property matches a given regex.
 * 
 * @param {RegExp} regex - Regular expression to match against the 'word' field.
 */
function lookupWordsByRegex(regex) {
    worker.postMessage({
        type: 'lookupByRegex',
        regex: regex.source,
        flags: regex.flags
    });
}

// Handle results from regex lookup
worker.onmessage = function (e) {
    const msg = e.data;

    if (msg.type === "regexResults") {
        /** @type {Array<Object>} */
        const matches = msg.results;

        console.log("Regex match results:", matches);
        // Do something useful here (e.g., render to UI)
    }

    // Existing fetch handler
    else if (msg.type === "fetch") {
        // ... your existing AJAX handling logic ...
    }
};


/**
 * Initiates the sync process when the DOM is ready.
 * Sends the 'startSync' message to the worker, including the root path for future imports or fetches.
 */
$(document).ready( function() {
    worker.postMessage({
        type: 'startSync',
        root_path: root_path
    });
});
