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

/** @type {boolean} Tracks whether the dictionary sync has completed */
var dictionary_sync_complete = false;

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
      msg.method, // HTTP method (e.g., 'get')
      "~ROOT~/" + url + "?domain=ajax", // URL with domain=ajax param appended
      msg.data, // Any additional request data
      function (response) {
        // Success callback
        worker.postMessage({
          type: "fetched",
          id: msg.id,
          success: true,
          payload: response,
        });
      },
      function (error) {
        // Failure callback
        worker.postMessage({
          type: "fetched",
          id: msg.id,
          success: false,
          payload: error,
        });
      }
    );
  } else if (msg.type === "regexResults") {
    populateSuggestedWords(
      msg.results,
      msg.totalMatches,
      msg.destination,
      msg.format
    );
  } else if (msg.type === "syncIncomplete") {
    // Update the UI
    $("#status-bar").html(
      "Synchronised " +
        msg.count +
        " words <i class='bi bi-info-square-fill' title='Synchronising the full dictionary happens once per browser and will take several minutes to an hour.'></i>"
    );
    // Now trigger the next partial sync
    worker.postMessage({
      type: "startSync",
      root_path: root_path,
    });
  } else if (msg.type === "syncComplete") {
    // Update the UI
    $("#status-bar").html("Dictionary sync complete");
    dictionary_sync_complete = true;
  }
};

/**
 * Populates the suggested word list with the returned matches in the specified format
 * @param {Array<Object>} matches an array of matched word entries
 * @param {int} totalMatches the total number of matches (in case we've paged the results) - currently unused
 * @param {string} destination the jQuery identifier of the location to put the results
 * @param {string} format the format in which to put the results (currently table-row|text)
 */
function populateSuggestedWords(matches, totalMatches, destination, format) {
  /** @type {string} */
  var output = "";
  switch (format) {
    case "table-row":
      output =
        "<tr><td class='suggested-word-list-item'>" +
        matches.results
          .map((o) => o.word)
          .join("</td></tr>\n<tr><td class='suggested-word-list-item'>") +
        "</td></tr>";
      break;
    case "text":
      output = matches.results.map((o) => o.bare_letters).join("\n");
      break;
    case "default":
      throw new Exception("Invalid format " + format + " specified.");
  }

  $(destination).html(output); // Works fine if it's plain text too
}

/**
 * Takes a pattern in the form A?B??C?D and returns a regular expression for searching a word list
 * @param {string} pattern the pattern to convert
 * @param {boolean} bareLettersVersion should Regex be suitable for searching a bare-letters list, or one with spaces and punctuation?
 * @returns RegExp the regular expression used to search
 */
function getRegexFromPattern(pattern, bareLettersVersion = true) {
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
}

/**
 * Requests a list of words from the worker whose `word` property matches a given regex.
 *
 * @param {RegExp} regex - Regular expression to match against the 'word' field.
 * @param {string} destination - Where to output the results (a jQuery query string)
 * @param {string} format - What form to post the results (currently only table-row or text)
 * @returns void
 */
function lookupWordsByRegex(regex, length, destination, format) {
  worker.postMessage({
    type: "lookupByRegex",
    regex: regex.source,
    flags: regex.flags,
    length: length,
    destination: destination,
    format: format,
  });
}

/**
 * Requests a list of words from the worker whose `word` property matches a given regex.
 *
 * @param {RegExp} regex - Regular expression to match against the 'word' field.
 * @param {string} destination - Where to output the results (a jQuery query string)
 * @param {string} format - What form to post the results (currently only table-row or text)
 * @returns void
 */
function lookupWordsByPattern(pattern, length, destination, format) {
  worker.postMessage({
    type: "lookupByPattern",
    pattern: pattern.toUpperCase(),
    length: length,
    destination: destination,
    format: format,
  });
  // dict_Worker.js returns message "regexResults" when complete
}

/**
 * Initiates the sync process when the DOM is ready.
 * Sends the 'startSync' message to the worker, including the root path for future imports or fetches.
 */
$(document).ready(function () {
  worker.postMessage({
    type: "startSync",
    root_path: root_path,
  });
});
