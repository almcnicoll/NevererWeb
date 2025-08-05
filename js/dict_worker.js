// js/dict_worker.js
//debugger;

// Load Dexie.js for IndexedDB management
importScripts('https://cdn.jsdelivr.net/npm/dexie@3/dist/dexie.min.js');
// Global flag for library loading (may be used for dynamic class loading in future)
self.librariesLoaded = false;

// --- IndexedDB Setup ---

/** @type {Dexie} */
const db = new Dexie('dictionary_cache');

// Define database schema
db.version(1).stores({
  tomes: 'id, name, source_type, source_format, writeable, user_id, last_updated',
  entries: '++id, tome_id, word, user_id, date_added',
  sync_meta: 'key, last_sync'
});
db.version(2).stores({
  tomes: 'id, name, source_type, source_format, writeable, user_id, last_updated',
  entries: '++id, tome_id, word, bare_letters, user_id, date_added',
  sync_meta: 'key, last_sync'
}).upgrade(tx => {
  return tx.table('entries').toCollection().modify(entry => {
    if (entry.bare_letters === undefined) {
      entry.bare_letters = TomeEntry.computeBareLetters(word); // Calculate bare_letters from word
    }
  });
});

// --- Utility Functions ---

/**
 * Generates a unique correlation ID to track message responses
 * @returns {string} A unique message ID (e.g., "msg_ab12cd34")
 */
function generateId() {
  return 'msg_' + Math.random().toString(36).slice(2);
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
    const msg = e.data;
    if (msg.type === 'fetched' && msg.id === id) {
      self.removeEventListener('message', listener);
      callback(msg.success, msg.payload);
    }
  };
  self.addEventListener('message', listener);
  self.postMessage({ type: 'fetch', method, data, id });
}

// --- Message Handling ---

/**
 * Handles incoming messages from the main thread
 * Currently only responds to `startSync`
 *
 * @param {MessageEvent} e
 */
self.onmessage = function (e) {
  const msg = e.data;
  if (msg.type === 'startSync') {
    self.root_path = msg.root_path;

    /*
    // Optional: dynamically load class definitions if needed in future
    if (!self.librariesLoaded) {
        importScripts('~ROOT~/js/class/Tome.js');
        importScripts('~ROOT~/js/class/TomeEntry.js');
        self.librariesLoaded = true;
    }
    */

    startSync();
  } else if (msg.type === "lookupByRegex") {
    const { regex, flags, destination, format, offset = 0, limit = Infinity } = msg;

    const pattern = new RegExp(regex, flags);

    getAllMatchingEntries(pattern, offset, limit).then(matches => {
        self.postMessage({
            type: "regexResults",
            results: matches,
            destination: destination,
            format: format
        });
    });
}
};

// --- Sync Process ---

/**
 * Starts the sync process by fetching Tomes and entries from the server
 * and updating the local IndexedDB cache accordingly.
 */
function startSync() {
  /** @type {{ url: string }} */
  let data = {
    url: "tome/*/list"
  };

  fetchFromServer('get', data, async (success, payload) => {
    if (!success) return;

    /** @type {Array<Object>} */
    const serverTomes = JSON.parse(payload); // TODO - allow for parse failures if e.g. error returned
    if (!(serverTomes instanceof Array || serverTomes instanceof Object)) { return; } // No Tomes
	// Get all local tome IDs
    const localTomeIds = new Set((await db.tomes.toArray()).map(t => t.id));
    // Extract server tome IDs
    const serverTomeIds = new Set(serverTomes.map(t => t.id));

    // Update tomes and remove obsolete ones
    await db.transaction('rw', db.tomes, db.entries, async () => {
      for (const tome of serverTomes) {
        await db.tomes.put(tome); // Insert or update
      }
      for (const localId of localTomeIds) {
        if (!serverTomeIds.has(localId)) {
          await db.tomes.delete(localId);
          await db.entries.where('tome_id').equals(localId).delete(); // Clean up orphan entries
        }
      }
    });

    // Fetch new or updated entries since the last sync
    const lastSync = (await db.sync_meta.get('entries'))?.last_sync ?? '1970-01-01T00:00:00Z';

    data = {
      url: "tome_entry/*/list",
      tome_ids: [...serverTomeIds],
      since: lastSync
    };

    fetchFromServer('get', data, async (success, payload) => {
      if (!success) return;

      /** @type {Array<Object>} */
      const newEntries = JSON.parse(payload); // TODO - allow for parse failures if e.g. error returned

      // Insert new/updated entries and record new sync time
      await db.transaction('rw', db.entries, db.sync_meta, async () => {
        for (const entry of newEntries) {
          if (entry.date_deleted) {
            await db.entries.where('[tome_id+word]').equals([entry.tome_id, entry.word]).delete();
          } else {
            await db.entries.put(entry);
          }
        }

        // Update the last sync timestamp
        await db.sync_meta.put({
          key: 'entries',
          last_sync: new Date().toISOString()
        });
      });
    });
  });
}

/**
 * Queries entries and filters them by regex on the 'word' field, with pagination.
 * Efficiently iterates entry-by-entry, collecting only the requested window while
 * optionally also counting the total number of matches.
 * 
 * @param {RegExp} pattern - Compiled regex to test against each entry's `word`.
 * @param {number} [offset=0] - Number of matching results to skip before collecting.
 * @param {number} [limit=Infinity] - Maximum number of matching results to return.
 * @param {boolean} [computeTotal=true] - Whether to compute the total match count.
 * @returns {Promise<{ results: Array<Object>, totalMatches: number | null }>} 
 *          Object containing the paginated results and totalMatches (or null if skipped).
 */
async function getAllMatchingEntries(pattern, offset = 0, limit = Infinity, computeTotal = true) {
  const results = [];
  let matchedCount = 0;      // Count of matches seen so far (for offset/limit logic)
  let totalMatches = 0;     // Overall matches if computeTotal is true

  // Iterate over entries one by one. Dexie's each() lets us break early by returning false.
  await db.entries.each(entry => {
    if (pattern.test(entry.word)) {
      // Always increment totalMatches if requested
      if (computeTotal) {
        totalMatches++;
      }

      // If we've skipped enough for offset, start collecting into results
      if (matchedCount >= offset) {
        if (results.length < limit) {
          results.push(entry);
        }
      }

      matchedCount++;

      // If we are not required to compute totalMatches and have enough results, we can stop early
      if (!computeTotal && results.length >= limit) {
        return false; // stops iteration
      }
    }
    // Continue iteration
  });

  // If computeTotal was false, we don't know total matches: set to null
  return {
    results,
    totalMatches: computeTotal ? totalMatches : null
  };
}
