"use strict";
/**
 * Verifies the fix for the anagram-abort race (previously documented here as a known
 * issue - see the #30 fix's original abortAnagram boolean).
 *
 * The old design had two problems:
 *  1. abortAnagram was a single shared flag that the "getAnagrams" handler unconditionally
 *     reset to false right before starting *its own* search - including when that search
 *     was the one meant to supersede an older, still in-flight request. So a new request's
 *     own startup could silently un-abort an older one instead of cancelling it.
 *  2. search() was a plain synchronous recursion with no yield point, so even a correctly
 *     set flag couldn't interrupt a search that had already started - the worker can't
 *     process any new postMessage input until the current synchronous call stack unwinds.
 *
 * The fix replaces the boolean with a per-request id (latestAnagramRequestId): each
 * getAnagrams() call claims the next id and carries it through the whole pipeline,
 * bailing out wherever it finds itself no longer current. search() also now yields
 * periodically (via a real setTimeout, so queued messages actually get a chance to run)
 * so a request that's already mid-recursion can still be interrupted, not just one that
 * hasn't started yet.
 */
const test = require("node:test");
const assert = require("node:assert/strict");
const { loadWorkerScript, toPlain, flush, flushUntil } = require("./helpers");

function makeEntry(word) {
    const row = { word };
    for (let i = 0; i < 26; i++) row[String.fromCharCode(97 + i)] = 0;
    for (const ch of word.toLowerCase()) {
        if (row[ch] !== undefined) row[ch]++;
    }
    return row;
}

function anagramResults(posted) {
    return posted.filter((m) => m.type === "anagramResults");
}

test("a request superseded while still waiting on its db fetch never posts stale results", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AT" } }); // old - superseded before it can even start searching
    sandbox.self.onmessage({ data: { type: "abortAnagrams" } });
    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AT" } }); // new

    await flushUntil(() => anagramResults(posted).length > 0);
    await flush(); // one more tick - if the old request were (still, wrongly) going to post, give it the chance

    const results = anagramResults(posted);
    assert.equal(results.length, 1, "the superseded request must not post");
    assert.deepEqual(toPlain(results[0].results), [["AT"], ["A", "T"]]);
});

test("a request already mid-recursion is interrupted at its next yield point and does not post", async () => {
    const { sandbox, posted } = loadWorkerScript();
    // Force every single recursive call to yield, so the "old" search is guaranteed to be
    // suspended mid-recursion (not just mid db-fetch) when we supersede it, deterministically
    // and without needing a large candidate set or depending on real elapsed time.
    sandbox.anagramSearchTuning.yieldCheckStride = 1;
    sandbox.anagramSearchTuning.yieldAfterMs = 0;
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("B"), makeEntry("AB"), makeEntry("BA")];

    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AB" } }); // old, will be interrupted mid-search
    await flush(); // let it clear its db fetch and suspend at its first search() yield point

    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AB" } }); // new, supersedes it

    await flushUntil(() => anagramResults(posted).length > 0);
    await flush();

    const results = anagramResults(posted);
    assert.equal(results.length, 1, "the interrupted search must not post its (partial or complete) results");
    // hand-verified: source AB against candidates A/AB/B/BA -> 3 solutions, fewer-words-first then alphabetical
    assert.deepEqual(toPlain(results[0].results), [["AB"], ["BA"], ["A", "B"]]);
});

test("abortAnagrams alone (no follow-up search) cancels an in-flight search with no post at all", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.anagramSearchTuning.yieldCheckStride = 1;
    sandbox.anagramSearchTuning.yieldAfterMs = 0;
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("B"), makeEntry("AB"), makeEntry("BA")];

    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AB" } });
    await flush();
    sandbox.self.onmessage({ data: { type: "abortAnagrams" } });

    // Drain a generous number of ticks - the search should never reach postMessage.
    for (let i = 0; i < 25; i++) await flush();

    assert.equal(anagramResults(posted).length, 0, "aborting with no replacement search must not post anything");
});

test("a normal search (default tuning, well under the yield threshold) is unaffected by the yield machinery", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    await sandbox.getAnagrams("AT"); // default tuning never triggers a yield for a search this small

    const msg = anagramResults(posted)[0];
    assert.ok(msg);
    assert.deepEqual(toPlain(msg.results), [["AT"], ["A", "T"]]);
});

test("rapid-fire requests: only the last one posts, and its result is correct", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.anagramSearchTuning.yieldCheckStride = 1;
    sandbox.anagramSearchTuning.yieldAfterMs = 0;
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("B"), makeEntry("AB"), makeEntry("BA"), makeEntry("T"), makeEntry("AT")];

    // Simulate a user editing the anagram field several times in quick succession
    // (dictionary.getAnagrams() sends abortAnagrams then getAnagrams for each keystroke/change).
    for (const word of ["A", "AB", "AT"]) {
        sandbox.self.onmessage({ data: { type: "abortAnagrams" } });
        sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: word } });
    }

    await flushUntil(() => anagramResults(posted).length > 0);
    await flush();
    await flush();

    const results = anagramResults(posted);
    assert.equal(results.length, 1, "only the final request should ever post");
    assert.deepEqual(toPlain(results[0].results), [["AT"], ["A", "T"]]);
});
