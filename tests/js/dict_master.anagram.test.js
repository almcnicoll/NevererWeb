"use strict";
/**
 * #31 (filter words out of the anagram list): the master-side half of the contract -
 * dictionary.parseExcludeWords() turns free text into a word list, and
 * dictionary.getAnagrams() forwards it to the worker. The worker-side filtering itself
 * is covered in tests/js/dict_worker.anagram.test.js.
 */
const test = require("node:test");
const assert = require("node:assert/strict");
const { loadMasterScript, toPlain } = require("./helpers");

test("parseExcludeWords: splits on spaces and/or commas, trims, and upper-cases", () => {
    const { sandbox } = loadMasterScript();
    assert.deepEqual(toPlain(sandbox.dictionary.parseExcludeWords("cat dog")), ["CAT", "DOG"]);
    assert.deepEqual(toPlain(sandbox.dictionary.parseExcludeWords("cat, dog")), ["CAT", "DOG"]);
    assert.deepEqual(toPlain(sandbox.dictionary.parseExcludeWords("cat,dog,  emu ")), ["CAT", "DOG", "EMU"]);
});

test("parseExcludeWords: blank/whitespace-only input yields an empty list", () => {
    const { sandbox } = loadMasterScript();
    assert.deepEqual(toPlain(sandbox.dictionary.parseExcludeWords("")), []);
    assert.deepEqual(toPlain(sandbox.dictionary.parseExcludeWords("   ")), []);
    assert.deepEqual(toPlain(sandbox.dictionary.parseExcludeWords(undefined)), []);
});

test("getAnagrams: forwards excludeWords to the worker alongside sourceWord", () => {
    const { sandbox } = loadMasterScript();

    sandbox.dictionary.getAnagrams("LISTEN", ["SILENT"]);

    const posted = sandbox.dictionary.worker.posted;
    const getMsg = posted.find((m) => m.type === "getAnagrams");
    assert.ok(getMsg);
    assert.equal(getMsg.sourceWord, "LISTEN");
    assert.deepEqual(toPlain(getMsg.excludeWords), ["SILENT"]);
});

test("getAnagrams: excludeWords defaults to an empty array when omitted", () => {
    const { sandbox } = loadMasterScript();

    sandbox.dictionary.getAnagrams("LISTEN");

    const getMsg = sandbox.dictionary.worker.posted.find((m) => m.type === "getAnagrams");
    assert.deepEqual(toPlain(getMsg.excludeWords), []);
});
