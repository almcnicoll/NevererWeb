// js/dict_worker.js
//debugger;

self.librariesLoaded = false;

importScripts('https://cdn.jsdelivr.net/npm/dexie@3/dist/dexie.min.js');

// IndexedDB setup
/** @type {Dexie} */
const db = new Dexie('dictionary_cache');

db.version(1).stores({
  tomes: 'id, name, source_type, source_format, writeable, user_id, last_updated',
  entries: '++id, tome_id, word, user_id, date_added',
  sync_meta: 'key, last_sync'
});

/**
 * Generates a unique correlation ID for message tracking
 * @returns {string}
 */
function generateId() {
  return 'msg_' + Math.random().toString(36).slice(2);
}

/**
 * Send a request to the main thread to make an AJAX call
 * @param {string} method 
 * @param {*} data 
 * @param {function} callback 
 */
function fetchFromServer(method, data, callback) {
  const id = generateId();
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

self.onmessage = function (e) {
  const msg = e.data;
  if (msg.type === 'startSync') {
    self.root_path = msg.root_path;
    /*if (!self.librariesLoaded) {
        importScripts('~ROOT~/js/class/Tome.js');
        importScripts('~ROOT~/js/class/TomeEntry.js');
        self.librariesLoaded = true;
    }*/
    startSync();
  }
};

/**
 * Starts the sync process
 */
function startSync() {
  data = {};
  data.url = "tome/*/list";
  fetchFromServer('get', data, async (success, payload) => {
    if (!success) return;

    const serverTomes = payload;

    const localTomeIds = new Set((await db.tomes.toArray()).map(t => t.id));
    const serverTomeIds = new Set(serverTomes.map(t => t.id));

    await db.transaction('rw', db.tomes, db.entries, async () => {
      for (const tome of serverTomes) {
        await db.tomes.put(tome);
      }
      for (const localId of localTomeIds) {
        if (!serverTomeIds.has(localId)) {
          await db.tomes.delete(localId);
          await db.entries.where('tome_id').equals(localId).delete();
        }
      }
    });

    const lastSync = (await db.sync_meta.get('entries'))?.last_sync ?? '1970-01-01T00:00:00Z';

    fetchFromServer('get', {
      tome_ids: [...serverTomeIds],
      since: lastSync
    }, async (success, payload) => {
      if (!success) return;

      const newEntries = payload;

      await db.transaction('rw', db.entries, db.sync_meta, async () => {
        for (const entry of newEntries) {
          if (entry.date_deleted) {
            await db.entries.where('[tome_id+word]').equals([entry.tome_id, entry.word]).delete();
          } else {
            await db.entries.put(entry);
          }
        }
        await db.sync_meta.put({ key: 'entries', last_sync: new Date().toISOString() });
      });
    });
  });
}
