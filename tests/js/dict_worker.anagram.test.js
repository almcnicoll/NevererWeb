"use strict";
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

test("wordToVector counts letters A-Z, ignoring non-letters", () => {
    const { sandbox } = loadWorkerScript();
    const v = sandbox.wordToVector("Cat!");
    assert.equal(v[0], 1); // A
    assert.equal(v[2], 1); // C
    assert.equal(v[19], 1); // T
    assert.equal(v.reduce((a, b) => a + b, 0), 3);
});

test("vSubtractIfPossible fails (returns null) when a letter would go negative", () => {
    const { sandbox } = loadWorkerScript();
    const remaining = sandbox.wordToVector("CAT");
    const tooMany = sandbox.wordToVector("CATS");
    assert.equal(sandbox.vSubtractIfPossible(remaining, tooMany), null);
});

test("vSubtractIfPossible succeeds and returns the leftover vector", () => {
    const { sandbox } = loadWorkerScript();
    const remaining = sandbox.wordToVector("CAT");
    const used = sandbox.wordToVector("AT");
    const left = sandbox.vSubtractIfPossible(remaining, used);
    assert.notEqual(left, null);
    assert.equal(left.reduce((a, b) => a + b, 0), 1); // just "C" left
});

test("getRegexFromPattern (bare-letters mode) turns ? into . and upper-cases", () => {
    const { sandbox } = loadWorkerScript();
    const re = sandbox.getRegexFromPattern("c?t");
    assert.ok(re.test("CAT"));
    assert.ok(!re.test("COAT"));
});

test("getAnagrams: fully hand-verified small case (source AT, candidates A/AT/T)", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    await sandbox.getAnagrams("AT");

    const msg = posted.find((m) => m.type === "anagramResults");
    assert.ok(msg, "should post an anagramResults message");
    // fewer-words-first ordering means the single-word solution sorts before the two-word one
    assert.deepEqual(toPlain(msg.results), [["AT"], ["A", "T"]]);
});

test("getAnagrams: candidates whose letters aren't a subset of the source are excluded", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("CAT")]; // "CAT" doesn't fit inside "DOG"

    await sandbox.getAnagrams("DOG");

    const msg = posted.find((m) => m.type === "anagramResults");
    assert.deepEqual(toPlain(msg.results), []);
});

test("getAnagrams: exact single-candidate match returns that one solution", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("DOG")];

    await sandbox.getAnagrams("DOG");

    const msg = posted.find((m) => m.type === "anagramResults");
    assert.deepEqual(toPlain(msg.results), [["DOG"]]);
});

test("getAnagrams: excludeWords (#31) removes matching candidates from the pool entirely", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    await sandbox.getAnagrams("AT", ["AT"]);

    const msg = posted.find((m) => m.type === "anagramResults");
    // "AT" itself is gone, and any solution that would have needed it - here there
    // isn't one, but the point is the excluded word can never appear in output
    assert.deepEqual(toPlain(msg.results), [["A", "T"]]);
});

test("getAnagrams: excludeWords is case-insensitive and can knock out an entire solution", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    await sandbox.getAnagrams("AT", ["a", "t"]); // lowercase input, e.g. straight from a text box

    const msg = posted.find((m) => m.type === "anagramResults");
    // both "A" and "T" excluded -> the two-word solution can't be built; only "AT" survives
    assert.deepEqual(toPlain(msg.results), [["AT"]]);
});

test("getAnagrams: an empty/omitted excludeWords list behaves exactly as before", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    await sandbox.getAnagrams("AT", []);

    const msg = posted.find((m) => m.type === "anagramResults");
    assert.deepEqual(toPlain(msg.results), [["AT"], ["A", "T"]]);
});

test("getAnagrams via the message interface (single request) completes and posts once", async () => {
    const { sandbox, posted } = loadWorkerScript();
    sandbox.db.entries.rows = [makeEntry("A"), makeEntry("AT"), makeEntry("T")];

    sandbox.self.onmessage({ data: { type: "getAnagrams", sourceWord: "AT" } });
    await flush();

    const results = posted.filter((m) => m.type === "anagramResults");
    assert.equal(results.length, 1);
    assert.deepEqual(toPlain(results[0].results), [["AT"], ["A", "T"]]);
});
