//debugger; // Uncomment to open this file in Dev Tools

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
    tomes: "id, name, source_type, source_format, writeable, user_id, last_updated",
    entries: "id, tome_id, word, bare_letters, user_id, date_added, length",
    sync_meta: "key, last_sync, last_offset", // keep last_offset if used
});
// Version 2 schema with compound indexes
db.version(2).stores({
    tomes: "id, name, source_type, source_format, writeable, user_id, last_updated",
    entries: "id, word, bare_letters, length, [length+bare_letters]",
    sync_meta: "key, last_sync, last_offset", // keep last_offset if used
});
db.version(3).stores({
    tomes: "id, name, source_type, source_format, writeable, user_id, last_updated",
    entries: "id, word, bare_letters, length, [length+bare_letters]",
    clues: "id, word, tome_id, user_id, date_added, question, cryptic, [word+tome_id]",
    sync_meta: "key, last_sync, last_offset",
});

// #endregion

// #region VARIABLES
let pattern = "";
let destination = "";
let format = "";
let length = 0;
let offset = 0;
let limit = Infinity;
let serverTomes;
let serverTomeIds = new Set();
let localTomes;
let localTomeIds = new Set();
let obsoleteTomeIds;
let eMeta;
let cMeta;
const SyncLimit = 100;
let thisSyncStamp;
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
let fetchReturnCallbacks = {};
function fetchFromServer(method, data, callback) {
    const id = generateId();
    fetchReturnCallbacks[id] = callback;
    self.postMessage({ type: "fetch", method, data, id });
}
// #endregion

// #region MESSAGE LOOP
/**
 * Handles incoming messages from the main thread
 *
 * @param {MessageEvent} e
 */

self.onmessage = function (e) {
    const msg = e.data;
    switch (msg.type) {
        case "init.tomeList":
            fetchTomeList();
            break;
        case "init.syncMetadata":
            fetchMetadata();
            break;
        case "continueSyncEntries":
            doSyncEntries();
            break;
        case "continueSyncClues":
            doSyncClues();
            break;
        case "fetched": // return from fetchFromServer
            let thisFetchCallback = fetchReturnCallbacks[msg.id];
            if (thisFetchCallback !== null) {
                thisFetchCallback(msg.success, msg.payload);
            }
            fetchReturnCallbacks[msg.id] = null;
            break;
        case "lookupByRegex":
            ({ regex, flags, destination, format, length, offset = 0, limit = Infinity } = msg);

            pattern = new RegExp(regex, flags);

            getAllMatchingEntriesByRegex(pattern, length, offset, limit).then((matches) => {
                self.postMessage({
                    type: "regexResults",
                    results: matches,
                    offset: offset,
                    limit: limit,
                    destination: destination,
                    format: format,
                });
            });
            break;
        case "lookupByPattern":
            ({ pattern = "", destination, format, length, offset = 0, limit = Infinity } = msg);

            getAllMatchingEntriesByPattern(pattern, length, offset, limit).then((matches) => {
                self.postMessage({
                    type: "regexResults",
                    results: matches,
                    offset: offset,
                    limit: limit,
                    destination: destination,
                    format: format,
                });
                getAllMatchingTomeClues(matches).then((tomeClues) => {
                    self.postMessage({
                        type: "tomeClueResults",
                        results: tomeClues,
                    });
                });
            });
            break;
        case "getAnagrams":
            ({ sourceWord } = msg);
            getAnagrams(sourceWord);
            break;
        default:
            console.log("Unexpected message " + msg.type + " sent to dict_worker.js");
            break;
    }
};
// #endregion

// #region DICTIONARY SYNC FUNCTIONS
/**
 * Fetches Tomes from the server
 * and updates the local IndexedDB cache
 */
function fetchTomeList() {
    // Get list of tomes from server
    /** @type {{ url: string }} */
    let data = {
        url: "tome/*/list",
    };
    fetchFromServer("get", data, parseTomeList); // end server-fetch function
}
async function parseTomeList(success, payload) {
    if (!success) return;

    try {
        serverTomes = JSON.parse(payload);
    } catch (e) {
        console.error("Failed to parse Tomes payload:", e); // TODO - throw this error properly (will require posting a message)
        return;
    }

    if (!Array.isArray(serverTomes)) return;

    serverTomeIds = new Set(serverTomes.map((t) => t.id));
    localTomes = await db.tomes.toArray();
    localTomeIds = new Set(localTomes.map((t) => t.id));

    // Identify obsolete tome IDs
    obsoleteTomeIds = [...localTomeIds].filter((id) => !serverTomeIds.has(id));

    // Update tomes and remove obsolete ones
    db.transaction("rw", db.tomes, db.entries, async function () {
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
    }).then(function () {
        // Now tell the main process that we're done with this part
        var msgId = generateId();
        var msgData = {
            type: "initialised",
            subType: "tomeList",
            msgId,
        };
        self.postMessage(msgData);
    });
}

async function fetchMetadata(payload) {
    eMeta = (await db.sync_meta.get("entries")) || {};
    eMeta.last_sync = eMeta.last_sync || "1970-01-01T00:00:00Z";
    eMeta.last_offset = eMeta.last_offset || 0; // Could be null if it's our first sync or if our last sync completed all rows
    cMeta = (await db.sync_meta.get("clues")) || {};
    cMeta.last_sync = cMeta.last_sync || "1970-01-01T00:00:00Z";
    cMeta.last_offset = cMeta.last_offset || 0; // Could be null if it's our first sync or if our last sync completed all rows
    // Tell the main thread that we're done here
    var msgId = generateId();
    var msgData = {
        type: "initialised",
        subType: "syncMetadata",
        msgId,
    };
    self.postMessage(msgData);
}

function doSyncEntries() {
    {
        // Set up fetch
        data = {
            url: "tome_entry/*/list",
            tome_ids: [...serverTomeIds],
            since: eMeta.last_sync,
            offset: eMeta.last_offset,
            limit: SyncLimit,
        };

        // Fetch next batch of entries from the server
        thisSyncStamp = new Date().toISOString(); // Store timestamp of last request, for writing to metadata on completion
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
                    await db.entries.where("[tome_id+word]").equals([tome_id, word]).delete();
                }

                // Bulk insert/update new or changed entries
                if (entriesToPut.length > 0) {
                    await db.entries.bulkPut(entriesToPut);
                }

                // Update sync metadata
                if (nextOffset == null) {
                    // We're done
                    // Update the date
                    eMeta.last_offset = null;
                    eMeta.last_sync = thisSyncStamp;
                    await db.sync_meta.put({
                        key: "entries",
                        last_sync: thisSyncStamp,
                        last_offset: nextOffset,
                    });
                    // Tell the main thread that our sync is complete
                    var msgId = generateId();
                    self.postMessage({ type: "syncCompleteEntries", msgId });
                } else {
                    // There's more to retrieve
                    // Don't update the date
                    eMeta.last_offset = nextOffset;
                    await db.sync_meta.put({
                        key: "entries",
                        last_offset: nextOffset,
                    });
                    // Tell the main thread that our sync is partially complete
                    var msgId = generateId();
                    self.postMessage({
                        type: "syncIncompleteEntries",
                        count: nextOffset,
                        msgId,
                    });
                }
            });
        });
    }
}

function doSyncClues() {
    {
        // Set up fetch
        data = {
            url: "tome_clue/*/list",
            tome_ids: [...serverTomeIds],
            since: cMeta.last_sync,
            offset: cMeta.last_offset,
            limit: SyncLimit,
        };

        // Fetch next batch of entries from the server
        thisSyncStamp = new Date().toISOString(); // Store timestamp of last request, for writing to metadata on completion
        fetchFromServer("get", data, async (success, payload) => {
            if (!success) return;

            let ajaxData;
            let newClues;
            let nextOffset;
            try {
                ajaxData = JSON.parse(payload);
                newClues = ajaxData.clues;
                nextOffset = ajaxData.nextOffset;
            } catch (e) {
                console.error("Failed to parse clues payload:", e);
                return;
            }

            if (!Array.isArray(newClues)) return;

            // Separate deletions and additions
            const cluesToDelete = [];
            const cluesToPut = [];

            for (const entry of newClues) {
                if (entry.date_deleted) {
                    cluesToDelete.push([entry.tome_id, entry.word]);
                } else {
                    cluesToPut.push(entry);
                }
            }

            await db.transaction("rw", db.clues, db.sync_meta, async () => {
                // Delete matching entries by [tome_id + word] — can't bulk delete compound index directly
                for (const [tome_id, word] of cluesToDelete) {
                    await db.clues.where("[word+tome_id]").equals([word, tome_id]).delete();
                }

                // Bulk insert/update new or changed entries
                if (cluesToPut.length > 0) {
                    await db.clues.bulkPut(cluesToPut);
                }

                // Update sync metadata
                if (nextOffset == null) {
                    // We're done
                    // Update the date
                    cMeta.last_offset = null;
                    cMeta.last_sync = thisSyncStamp;
                    await db.sync_meta.put({
                        key: "clues",
                        last_sync: thisSyncStamp,
                        last_offset: nextOffset,
                    });
                    // Tell the main thread that our sync is complete
                    var msgId = generateId();
                    self.postMessage({ type: "syncCompleteClues", msgId });
                } else {
                    // There's more to retrieve
                    // Don't update the date
                    cMeta.last_offset = nextOffset;
                    await db.sync_meta.put({
                        key: "clues",
                        last_offset: nextOffset,
                    });
                    // Tell the main thread that our sync is partially complete
                    var msgId = generateId();
                    self.postMessage({
                        type: "syncIncompleteClues",
                        count: nextOffset,
                        msgId,
                    });
                }
            });
        });
    }
}
// #endregion

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
async function getAllMatchingEntriesByRegex(pattern, length, offset = 0, limit = 50) {
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
async function getAllMatchingEntriesByPattern(pattern, length, offset = 0, limit = 50) {
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
    const filteredByIndex = await db.entries.where("[length+bare_letters]").between(lowerBound, upperBound).toArray();

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

/**
 * Retrieves matching clues from the local TomeClues data store
 * @param {{ results: Array<{ word: string }> }} matches an object containing a property results with an array of pattern-matching results in it
 * @returns {Promise<Array<{ id:number, word:string, tome_id:number, user_id:number, date_added:string, question:string, explanation?:string }>>} a flat array of matching clues from the local store
 */
async function getAllMatchingTomeClues(matches) {
    if (!matches || !Array.isArray(matches.results)) return [];

    // Normalise to lower-case because IndexedDB matching is case-sensitive
    const wanted = matches.results.map((r) => r.word?.toLowerCase()).filter(Boolean);

    if (wanted.length === 0) return [];

    // Deduplicate words
    const unique = [...new Set(wanted)];

    // Query all words in parallel
    const results = await Promise.all(
        unique.map((w) =>
            db.clues
                .where("word")
                .equalsIgnoreCase(w) // Dexie helper: case-insensitive match
                .toArray()
        )
    );

    // Flatten output
    return results.flat();
}

/**
 * Converts a string to a 26-dimensional vector
 * @param {string} word the word to vectorise
 * @returns {Uint8Array} the vector equivalent of the word
 */
function wordToVector(word) {
    word = word.toUpperCase();
    const v = new Uint8Array(26);
    for (let i = 0; i < word.length; i++) {
        const code = word.charCodeAt(i) - 65;
        if (code >= 0 && code < 26) v[code]++;
    }
    return v;
}

/**
 * Attempts to subtract vector w from vector R, failing if subtraction is negative in any dimension
 * @param {Uint8Array} R Currently-remaining vector
 * @param {Uint8Array} w Candidate word vector
 * @returns {Uint8Array|null} the resulting remaining-letters vector or null if subtraction fails
 */
function vSubtractIfPossible(R, w) {
    const out = new Uint8Array(26);
    for (let i = 0; i < 26; i++) {
        const d = R[i] - w[i];
        if (d < 0) return null;
        out[i] = d;
    }
    return out;
}

/**
 * Converts a word's a-z letter counts into a vector
 * @param {object} row - DB row with a-z counts
 * @returns {Uint8Array} 26-length vector
 */
function rowToVector(row) {
    const v = new Uint8Array(26);
    for (let i = 0; i < 26; i++) {
        const letter = String.fromCharCode(97 + i); // 'a'..'z'
        v[i] = row[letter] || 0;
    }
    return v;
}

/**
 * Converts a vector to a string key for memoisation
 * @param {Uint8Array} v
 * @returns {string}
 */
function vectorKey(v) {
    let s = "";
    for (let i = 0; i < 26; i++) {
        s += String.fromCharCode(65 + i).repeat(v[i]);
    }
    return s;
}

/**
 * Recursive DFS search
 * @param {Uint8Array} remaining - letters left
 * @param {Array} candidates - array of { vector, words }
 * @param {number} startIndex - index in candidates array to start at
 * @param {Array} currentSolution - array of candidate indices
 * @param {Set} deadStates - memoisation set
 * @param {Array} solutions - collected solutions
 */
function search(remaining, candidates, startIndex, currentSolution, deadStates, solutions) {
    const key = vectorKey(remaining);
    if (deadStates.has(key)) return;

    if (key.length === 0) {
        // Base case: no letters left
        solutions.push([...currentSolution]);
        return;
    }

    let found = false;

    for (let i = startIndex; i < candidates.length; i++) {
        const next = vSubtractIfPossible(remaining, candidates[i].vector);
        if (!next) continue;

        currentSolution.push(i);
        search(next, candidates, i, currentSolution, deadStates, solutions); // i, not i+1
        currentSolution.pop();

        found = true;
    }

    if (!found) {
        deadStates.add(key);
    }
}

/**
 * Main entry point
 * @param {string} sourceWord - already uppercase, no spaces/punctuation
 */
async function getAnagrams(sourceWord) {
    // Convert source word to vector
    const sourceVec = wordToVector(sourceWord);

    // Load candidate words from Dexie
    // Pre-filter: only words whose letters are subset of sourceVec
    const allRows = await db.entries.toArray();
    const candidates = [];
    for (const row of allRows) {
        const vec = rowToVector(row);
        let fits = true;
        for (let i = 0; i < 26; i++) {
            if (vec[i] > sourceVec[i]) {
                fits = false;
                break;
            }
        }
        if (fits) {
            candidates.push({ vector: vec, words: [row.word] });
        }
    }

    // Sort candidates lexicographically to enforce combination order
    candidates.sort((a, b) => a.words[0].localeCompare(b.words[0]));

    const deadStates = new Set();
    const solutions = [];

    search(sourceVec, candidates, 0, [], deadStates, solutions);

    // Expand candidate indices to actual words
    const expanded = solutions.map((sol) => sol.map((i) => candidates[i].words[0]));

    // Sort to fewer, longer words first
    const ordered = expanded
        .map((sol) => ({
            sol,
            count: sol.length,
            chars: sol.reduce((s, w) => s + w.length, 0),
            alpha: sol.join(" "), // stable, readable tie-breaker
        }))
        .sort(
            (a, b) =>
                a.count - b.count || // 1. fewer words first
                b.chars - a.chars || // 2. longer total length first
                a.alpha.localeCompare(b.alpha) // 3. alphabetical
        )
        .map((x) => x.sol);

    self.postMessage({
        type: "anagramResults",
        results: ordered,
    });

    return;
}
// #endregion
