// debugger; // Uncomment to open this file in Dev Tools

// #region SETUP
// Load Dexie.js for IndexedDB management
importScripts("https://cdn.jsdelivr.net/npm/dexie@3/dist/dexie.min.js");
// Global flag for library loading (may be used for dynamic class loading in future)
self.librariesLoaded = false;

/** DEXIE.JS SETUP */
// Name of the old and new databases
const DB_NAME_V01 = "dictionary_cache"; // No longer used - will need manual deletion
const DB_NAME_V02 = "tome_cache";

/** @type {Dexie} */
const db = new Dexie(DB_NAME_V02);

// Define database schema
db.version(1).stores({
  tomes:
    "id, name, source_type, source_format, writeable, user_id, last_updated",
  entries: "id, tome_id, word, bare_letters, user_id, date_added, length",
  sync_meta: "key, last_sync, last_offset", // keep last_offset if used
});
// Version 2 schema with compound indexes
db.version(2).stores({
  tomes:
    "id, name, source_type, source_format, writeable, user_id, last_updated",
  entries: "id,word,bare_letters,length,[length+bare_letters]",
  sync_meta: "key, last_sync, last_offset", // keep last_offset if used
});
// #endregion

// #region UTILITY FUNCTIONS
/**
 * Generates a unique correlation ID to track message responses
 * @returns {string} A unique message ID (e.g., "msg_ab12cd34")
 */
function generateId() {
  return "msg_" + Math.random().toString(36).slice(2);
}

/**
 * Requests the main thread to perform an AJAX call on the worker's behalf.
 * The main thread should respond with a `type: 'fetched'` message using the same ID.
 *
 * @param {string} method - HTTP method to use (e.g., 'get', 'post')
 * @param {*} data - Data payload to send with the request, including a 'url' property
 * @param {(success: boolean, payload: any) => void} callback - Callback invoked with the result
 */
function fetchFromServer(method, data, callback) {
  const id = generateId();

  /** @param {MessageEvent} e */
  const listener = (e) => {
    const msg = e.data; // TODO - move this to main message loop?
    if (msg.type === "fetched" && msg.id === id) {
      self.removeEventListener("message", listener);
      callback(msg.success, msg.payload);
    }
  };
  self.addEventListener("message", listener);
  self.postMessage({ type: "fetch", method, data, id });
}
// #endregion

// #region MESSAGE LOOP
/**
 * Handles incoming messages from the main thread
 *
 * @param {MessageEvent} e
 */

let pattern,destination,format,length,offset = 0,limit = Infinity;

self.onmessage = function (e) {
  const msg = e.data;
  switch (msg.type) {
    case 'initialiseSync':
      self.root_path = msg.root_path;
      initialiseSync(); // When finished, will post "syncInitialised" to main thread
      break;
    case 'continueSync':
      doSync();
      break;
    case 'lookupByRegex':
      ({
        regex,
        flags,
        destination,
        format,
        length,
        offset = 0,
        limit = Infinity,
      } = msg);

      const pattern = new RegExp(regex, flags);

      getAllMatchingEntriesByRegex(pattern, length, offset, limit).then(
        (matches) => {
          self.postMessage({
            type: "regexResults",
            results: matches,
            offset: offset,
            limit: limit,
            destination: destination,
            format: format,
          });
        }
      );
      break;
    case 'lookupByPattern':
      ({
        pattern,
        destination,
        format,
        length,
        offset = 0,
        limit = Infinity,
      } = msg);

      getAllMatchingEntriesByPattern(pattern, length, offset, limit).then(
        (matches) => {
          self.postMessage({
            type: "regexResults",
            results: matches,
            offset: offset,
            limit: limit,
            destination: destination,
            format: format,
          });
        }
      );
      break;
    default:
      console.log("Unexpected message "+msg.type+" sent to dict_worker.js");
      break;
    }
};
// #endregion

// #region VARIABLES
let serverTomes;
let serverTomeIds = new Set();
let localTomes;
let localTomeIds = new Set();
let obsoleteTomeIds;
let meta;
const SyncLimit = 100;
let thisSyncStamp;

// #endregion

// --- Dictionary sync Process ---
/**
 * Starts the sync process by fetching Tomes and entries from the server
 * and updating the local IndexedDB cache accordingly using bulk operations.
 */
function initialiseSync() {
  /** @type {{ url: string }} */
  let data = {
    url: "tome/*/list",
  };

  // Change to fetchFromServer("get", data, saveTomeList)
  // and use .then() chaining to call functions that are currently embedded lambdas below

  fetchFromServer("get", data, async (success, payload) => {
    if (!success) return;

    try {
      serverTomes = JSON.parse(payload);
    } catch (e) {
      console.error("Failed to parse Tomes payload:", e);
      return;
    }

    if (!Array.isArray(serverTomes)) return;

    serverTomeIds = new Set(serverTomes.map((t) => t.id));
    localTomes = await db.tomes.toArray();
    localTomeIds = new Set(localTomes.map((t) => t.id));

    // Identify obsolete tome IDs
    obsoleteTomeIds = [...localTomeIds].filter(
      (id) => !serverTomeIds.has(id)
    );

    // Update tomes and remove obsolete ones
    await db.transaction("rw", db.tomes, db.entries, async () => {
      // Bulk insert/update tomes
      await db.tomes.bulkPut(serverTomes);

      // Bulk delete tomes and their entries that are no longer on the server
      if (obsoleteTomeIds.length > 0) {
        await db.tomes.bulkDelete(obsoleteTomeIds);

        // Delete associated entries
        for (const id of obsoleteTomeIds) {
          await db.entries.where("tome_id").equals(id).delete(); // can't bulkDelete on compound index
        }
      }
      // Only now should we retrieve parameters for tome_entries sync
      meta = await (db.sync_meta.get("entries")) || {};
      meta.last_sync = meta.last_sync || "1970-01-01T00:00:00Z"; // TODO - HIGH this wasn't working - not sure if it is now
      meta.last_offset = meta.last_offset || 0; // Could be null if it's our first sync or if our last sync completed all rows
      // Tell the main thread that our sync is ready to go
      var msgId = generateId();
      self.postMessage({ type: "syncInitialised", msgId });
    }); // end database write function
  }); // end server-fetch function
} // end startSync function

function doSync() {
{
    // Set up fetch
    data = {
      url: "tome_entry/*/list",
      tome_ids: [...serverTomeIds],
      since: meta.last_sync,
      offset: meta.last_offset,
      limit: SyncLimit,
    };

    // Fetch next batch of entries from the server
    thisSyncStamp = new Date(); // Store timestamp of last request, for writing to metadata on completion
    fetchFromServer("get", data, async (success, payload) => {
      if (!success) return;

      let ajaxData;
      let newEntries;
      let nextOffset;
      try {
        ajaxData = JSON.parse(payload);
        newEntries = ajaxData.entries;
        nextOffset = ajaxData.nextOffset;
      } catch (e) {
        console.error("Failed to parse entries payload:", e);
        return;
      }

      if (!Array.isArray(newEntries)) return;

      // Separate deletions and additions
      const entriesToDelete = [];
      const entriesToPut = [];

      for (const entry of newEntries) {
        if (entry.date_deleted) {
          entriesToDelete.push([entry.tome_id, entry.word]);
        } else {
          entriesToPut.push(entry);
        }
      }

      await db.transaction("rw", db.entries, db.sync_meta, async () => {
        // Delete matching entries by [tome_id + word] — can't bulk delete compound index directly
        for (const [tome_id, word] of entriesToDelete) {
          await db.entries
            .where("[tome_id+word]")
            .equals([tome_id, word])
            .delete();
        }

        // Bulk insert/update new or changed entries
        if (entriesToPut.length > 0) {
          await db.entries.bulkPut(entriesToPut);
        }

        // Update sync metadata
        if (nextOffset == null) { // We're done
          await db.sync_meta.put({
            // Update the date
            key: "entries",
            last_sync: thisSyncStamp,
            last_offset: nextOffset,
          });
          // Tell the main thread that our sync is complete
          var msgId = generateId();
          self.postMessage({ type: "syncComplete", msgId });
        } else { // There's more to retrieve
          await db.sync_meta.put({
            // Don't update the date
            key: "entries",
            last_offset: nextOffset,
          });
          // Tell the main thread that our sync is partially complete
          var msgId = generateId();
          self.postMessage({
            type: "syncIncomplete",
            count: nextOffset,
            msgId,
          });
        }
      });
    });
  }
}

// #region FUNCTIONS
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
 * DEPRECATED / UNFINISHED Queries entries with a specific length and filters them by regex on the 'word' field.
 *
 * @param {RegExp} pattern - Compiled regex to match against each word.
 * @param {number} length - Word length to pre-filter with indexed search.
 * @param {number} [offset=0] - Number of entries to skip (pagination).
 * @param {number} [limit=50] - Max number of matching entries to return.
 * @returns {Promise<{ results: Array<Object>, total: number }>}
 */
async function getAllMatchingEntriesByRegex(
  pattern,
  length,
  offset = 0,
  limit = 50
) {
  // Step 1: Narrow the query using indexed `length`
  const entries = await db.entries.where("length").equals(length).toArray();

  // Step 2: Filter using RegExp in memory
  const filtered = entries.filter((entry) => pattern.test(entry.bare_letters));

  // Step 3: Paginate
  const paged = filtered.slice(offset, offset + limit);

  return {
    results: paged,
    total: filtered.length, // total *matched* count, not total in DB
  };
}

/**
 * The preferred way to retrieve possible word matches - supply a word in "pattern form" ( e.g. C??I?TM?S )
 * Note that record paging happens after all matching records have been retrieved - that is, it saves bandwidth but not db load
 * @param {string} pattern the pattern (in the form of letters and question marks) to search for
 * @param {int} length the length of the pattern
 * @param {int} offset the offset from which to retrieve records (for record paging)
 * @param {int} limit the limit of how many records to retrieve (for record paging)
 * @returns
 */
async function getAllMatchingEntriesByPattern(
  pattern,
  length,
  offset = 0,
  limit = 50
) {
  // Determine if there are any opening known letters to filter by
  const firstQM = pattern.indexOf("?");
  var searchString;
  switch (firstQM) {
    case -1:
      // No question marks - we have a whole string - simples!
      searchString = pattern;
      break;
    default:
      // Otherwise, use whatever letters we have (if any) as a prefix
      searchString = pattern.substr(0, firstQM);
  }
  // We can't do this in stages - we have to use the compound length+bare_letters index
  const lowerBound = [length, searchString];
  const upperBound = [length, searchString + "\uffff"];
  const filteredByIndex = await db.entries
    .where("[length+bare_letters]")
    .between(lowerBound, upperBound)
    .toArray();

  // Check for "no fixed letters" option
  const noLettersSpecified = pattern == new String("").repeat(length);

  // Now generate a regular expression and filter using RegExp in memory
  const rePattern = getRegexFromPattern(pattern, true);
  const filteredByRegex = noLettersSpecified
    ? filteredByIndex
    : filteredByIndex.filter((entry) => rePattern.test(entry.bare_letters));

  // Step 3: Paginate
  const paged = filteredByRegex.slice(offset, offset + limit);

  return {
    results: paged,
    total: filteredByRegex.length, // total *matched* count, not total in DB
  };
}
// #endregion