// js/dict_worker.js
//debugger;

// Load Dexie.js for IndexedDB management
importScripts("https://cdn.jsdelivr.net/npm/dexie@3/dist/dexie.min.js");
// Global flag for library loading (may be used for dynamic class loading in future)
self.librariesLoaded = false;

// --- IndexedDB Setup ---

/** @type {Dexie} */
const db = new Dexie("dictionary_cache");

// Define database schema
db.version(1).stores({
  tomes:
    "id, name, source_type, source_format, writeable, user_id, last_updated",
  entries: "++id, tome_id, word, user_id, date_added",
  sync_meta: "key, last_sync",
});
db.version(2)
  .stores({
    tomes:
      "id, name, source_type, source_format, writeable, user_id, last_updated",
    entries: "++id, tome_id, word, bare_letters, user_id, date_added",
    sync_meta: "key, last_sync",
  })
  .upgrade((tx) => {
    return tx
      .table("entries")
      .toCollection()
      .modify((entry) => {
        if (entry.bare_letters === undefined) {
          entry.bare_letters = TomeEntry.computeBareLetters(word); // Calculate bare_letters from word
        }
      });
  });
// Upgrade path: version 3 adds `length` index to entries
db.version(3)
  .stores({
    tomes:
      "id, name, source_type, source_format, writeable, user_id, last_updated",
    // Index `length` so we can efficiently filter by it
    entries: "++id, tome_id, word, bare_letters, user_id, date_added, length",
    sync_meta: "key, last_sync",
  })
  .upgrade(async (tx) => {
    // Populate length for existing entries if missing
    const all = await tx.table("entries").toArray();
    for (const entry of all) {
      if (entry.length === undefined || entry.length === null) {
        const computedLength = entry.bare_letters
          ? entry.bare_letters.length
          : 0;
        await tx.table("entries").update(entry.id, { length: computedLength });
      }
    }
  });
// Upgrade path: version 4 removes unnecessary indices from entries
db.version(4).stores({
  tomes:
    "id, name, source_type, source_format, writeable, user_id, last_updated",
  // Index `length` so we can efficiently filter by it
  entries: "++id, tome_id, bare_letters, length",
  sync_meta: "key, last_sync",
});
// Upgrade path: version 5 re-populates `length` where missing
db.version(5)
  .stores({
    tomes:
      "id, name, source_type, source_format, writeable, user_id, last_updated",
    entries: "++id, tome_id, word, bare_letters, user_id, date_added, length",
    sync_meta: "key, last_sync",
  })
  .upgrade(async (tx) => {
    const entries = tx.table("entries");
    const allMissing = await entries
      .filter((e) => !e.length && e.length !== 0)
      .toArray();
    for (const entry of allMissing) {
      const computedLength = entry.bare_letters ? entry.bare_letters.length : 0;
      await entries.update(entry.id, { length: computedLength });
    }
  });

// --- Utility Functions ---

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
    const msg = e.data;
    if (msg.type === "fetched" && msg.id === id) {
      self.removeEventListener("message", listener);
      callback(msg.success, msg.payload);
    }
  };
  self.addEventListener("message", listener);
  self.postMessage({ type: "fetch", method, data, id });
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
  if (msg.type === "startSync") {
    self.root_path = msg.root_path;
    startSync();
  } else if (msg.type === "lookupByRegex") {
    const {
      regex,
      flags,
      destination,
      format,
      length,
      offset = 0,
      limit = Infinity,
    } = msg;

    const pattern = new RegExp(regex, flags);

    getAllMatchingEntries(pattern, length, offset, limit).then((matches) => {
      self.postMessage({
        type: "regexResults",
        results: matches,
        offset: offset,
        limit: limit,
        destination: destination,
        format: format,
      });
    });
  }
};

// --- Dictionary sync Process ---
// TODO - HIGH see latest suggestion that uses bulk data methods
/**
 * Starts the sync process by fetching Tomes and entries from the server
 * and updating the local IndexedDB cache accordingly using bulk operations.
 */
const SyncLimit = 1000;
function startSync() {
  /** @type {{ url: string }} */
  let data = {
    url: "tome/*/list",
  };

  fetchFromServer("get", data, async (success, payload) => {
    if (!success) return;

    let serverTomes;
    try {
      serverTomes = JSON.parse(payload);
    } catch (e) {
      console.error("Failed to parse Tomes payload:", e);
      return;
    }

    if (!Array.isArray(serverTomes)) return;

    const serverTomeIds = new Set(serverTomes.map((t) => t.id));
    const localTomes = await db.tomes.toArray();
    const localTomeIds = new Set(localTomes.map((t) => t.id));

    // Identify obsolete tome IDs
    const obsoleteTomeIds = [...localTomeIds].filter(
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
    });

    // Fetch new/updated entries
    // const lastSync = (await db.sync_meta.get('entries'))?.last_sync ?? '1970-01-01T00:00:00Z';
    const meta = (await db.sync_meta.get("entries")) || {};
    const lastSync = meta.last_sync || "1970-01-01T00:00:00Z";
    const lastOffset = meta.last_offset || 0; // Could be null if it's our first sync or if our last sync completed all rows

    data = {
      url: "tome_entry/*/list",
      tome_ids: [...serverTomeIds],
      since: lastSync,
      offset: lastOffset, // TODO - HIGH this is problematic - won't work if we don't know whether it finished last time. Set to null when done?
      limit: SyncLimit,
    };

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
        if (nextOffset == null) {
          await db.sync_meta.put({
            // We're done - update the date
            key: "entries",
            last_sync: new Date().toISOString(),
            last_offset: nextOffset,
          });
          // Tell the main thread that our sync is complete
          var msgId = generateId();
          self.postMessage({ type: "syncComplete", msgId });
        } else {
          await db.sync_meta.put({
            // There's more to retrieve - don't update the date
            key: "entries",
            last_offset: nextOffset,
          });
          // Tell the main thread that our sync is partially complete
          var msgId = generateId();
          self.postMessage({ type: "syncIncomplete", msgId });
        }
      });
    });
  });
}

// TODO - HIGH don't call this for patterns with no fixed letters!
/**
 * Queries entries with a specific length and filters them by regex on the 'word' field.
 *
 * @param {RegExp} pattern - Compiled regex to match against each word.
 * @param {number} length - Word length to pre-filter with indexed search.
 * @param {number} [offset=0] - Number of entries to skip (pagination).
 * @param {number} [limit=50] - Max number of matching entries to return.
 * @returns {Promise<{ results: Array<Object>, total: number }>}
 */
async function getAllMatchingEntries(pattern, length, offset = 0, limit = 50) {
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
