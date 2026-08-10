"use strict";
/**
 * Investigates issue #30's fix ("New anagram request should abort previous ones").
 *
 * The fix added a module-level `abortAnagram` flag, set by an "abortAnagrams" message
 * and checked at the top of the recursive search() function. dict_master.js sends
 * "abortAnagrams" then "getAnagrams" back-to-back whenever a new anagram search starts.
 *
 * This file documents (rather than "fixes") a race in that design: search() is a plain
 * synchronous recursive function with no yield point, so once it starts running it can't
 * be interrupted by a later message - the worker can't process new postMessage input
 * until the current synchronous call stack unwinds. The only window where the flag can
 * matter is while a request is still suspended on `await db.entries.toArray()`. But the
 * "getAnagrams" handler *always* resets abortAnagram=false immediately before starting
 * its own search - including when it's the request that's supposed to be superseding an
 * older, still-in-flight one. So a second request's own startup silently un-aborts the
 * first request instead of cancelling it.
 *
 * Net effect: a slow, already-dispatched search is never actually cancelled by a newer
 * one; both run to completion and both post "anagramResults". The UI still ends up
 * showing the right final answer as long as the newer request's message resolves after
 * the older one's (see report), but the "abort" doesn't save any computation, which was
 * presumably the point of #30.
 */
const test = require("node:test");
const assert = require("node:assert/strict");
const { loadWorkerScript, toPlain, flush } = require("./helpers");

function makeEntry(word) {
    const row = { word };
    for (let i = 0; i < 26; i++) row[String.fromCharCode(97 + i)] = 0;
    for (const ch of word.toLowerCase()) {
        if (row[ch] !== undefined) row[ch]++;
    }
    return row;
}

test("KNOWN ISSUE: abortAnagrams cannot cancel a request already past its db-fetch await", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    // Kick off the first ("old") search. It runs synchronously up to its
    // `await db.entries.toArray()` and then suspends, handing control back to us.
    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AT" } });

    // Before the old search resumes, the master thread's abort+new-search sequence
    // (as sent by dictionary.getAnagrams() in dict_master.js) arrives.
    sandbox.self.onmessage({ data: { type: "abortAnagrams" } });
    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AT" } }); // "new" request

    // Let both suspended getAnagrams() calls resume and finish.
    await flush();

    const results = posted.filter((m) => m.type === "anagramResults");
    // If the abort worked as the issue title implies, we'd expect exactly 1 result
    // (the old search cancelled, only the new one posting). Today we get 2: the
    // "aborted" request still completes and posts a full result set.
    assert.equal(
        results.length,
        2,
        "documents that both the superseded and the new search complete and post results - " +
            "the abort only takes effect if a request hasn't reached db.entries.toArray() yet"
    );
    assert.deepEqual(toPlain(results[0].results), [["AT"], ["A", "T"]], "the 'aborted' search still ran to completion");
});
