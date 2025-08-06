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
// Upgrade path: version 3 adds `length` index to entries
db.version(3)
  .stores({
    tomes: 'id, name, source_type, source_format, writeable, user_id, last_updated',
    // Index `length` so we can efficiently filter by it
    entries: '++id, tome_id, word, bare_letters, user_id, date_added, length',
    sync_meta: 'key, last_sync'
  })
  .upgrade(async tx => {
    // Populate length for existing entries if missing
    const all = await tx.table('entries').toArray();
    for (const entry of all) {
      if (entry.length === undefined || entry.length === null) {
        const computedLength = entry.bare_letters ? entry.bare_letters.length : 0;
        await tx.table('entries').update(entry.id, { length: computedLength });
      }
    }
  });
  // Upgrade path: version 4 removes unnecessary indices from entries
  db.version(4)
  .stores({
    tomes: 'id, name, source_type, source_format, writeable, user_id, last_updated',
    // Index `length` so we can efficiently filter by it
    entries: '++id, tome_id, bare_letters, length',
    sync_meta: 'key, last_sync'
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
    const { regex, flags, destination, format, length, offset = 0, limit = Infinity } = msg;

    const pattern = new RegExp(regex, flags);

    getAllMatchingEntries(pattern, length, offset, limit).then(matches => {
        self.postMessage({
            type: "regexResults",
            results: matches,
            offset: offset,
            limit: limit,
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


// TODO - HIGH don't call this for patterns with no fixed letters!
/**
 * Queries entries matching a given length and regex on the 'word' field, with pagination.
 * Efficiently restricts by length first (using index), then tests regex, optionally computing total matches.
 * 
 * @param {RegExp} pattern - Compiled regex to test against each entry's `word`.
 * @param {number|null} lengthFilter - If provided, only entries with this `.length` are considered.
 * @param {number} [offset=0] - Number of matching results to skip before collecting.
 * @param {number} [limit=Infinity] - Maximum number of matching results to return.
 * @param {boolean} [computeTotal=true] - Whether to compute the total match count (ignoring pagination).
 * @returns {Promise<{ results: Array<Object>, totalMatches: number | null }>} Paginated matches and total.
 */
async function getAllMatchingEntries(pattern, lengthFilter = null, offset = 0, limit = Infinity, computeTotal = true) {
  const results = [];
  let matchedCount = 0;      // Count of matches seen so far for offset logic
  let totalMatches = 0;     // Overall matches if computeTotal is true

  // Use a collection prefiltered by length if lengthFilter is specified, else all entries
  let collection = lengthFilter !== null
    ? db.entries.where('length').equals(lengthFilter)
    : db.entries;
  /*collection.count().then(count => {
    console.log("Cut dictionary down to "+count+" entries before Regex search.");
  });*/

  // Iterate more efficiently
  await collection.each(entry => {
    if (pattern.test(entry.bare_letters)) {
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