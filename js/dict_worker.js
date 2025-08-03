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
    const { regex, flags } = msg;

    const pattern = new RegExp(regex, flags);

    getAllMatchingEntries(pattern).then(matches => {
        self.postMessage({
            type: "regexResults",
            results: matches
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
 * Queries all entries and filters them by regex on the 'word' field.
 * 
 * @param {RegExp} pattern - Compiled regex to match against each word.
 * @returns {Promise<Array<Object>>} Matching entries.
 */
async function getAllMatchingEntries(pattern) {
    /** @type {IDBObjectStore} */
    const store = await getObjectStore("entries", "readonly");

    return new Promise((resolve, reject) => {
        const results = [];
        const request = store.openCursor();

        request.onsuccess = function (event) {
            const cursor = event.target.result;
            if (cursor) {
                const entry = cursor.value;
                if (pattern.test(entry.word)) {
                    results.push(entry);
                }
                cursor.continue();
            } else {
                resolve(results);
            }
        };

        request.onerror = function (event) {
            reject(event.target.error);
        };
    });
}