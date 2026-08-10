"use strict";
/**
 * Verifies js/app.js's transferData() against the format emitted by the new
 * UI\DataTransfer PHP helper (tests/php/DataTransferTest.php) - PHP emits
 * <script type="application/json" class="data-transfer" data-scope="..."> as a "data
 * island"; transferData() (unchanged - this is pre-existing code) reads it on
 * document-ready and assigns each key into the given JS scope. This is the CSP-safe
 * replacement for inline <script> blocks with PHP-interpolated JS (#8).
 */
const test = require("node:test");
const assert = require("node:assert/strict");
const { loadAppScript } = require("./helpers");

test("transferData(): assigns JSON keys as globals when data-scope is 'window' (the default)", () => {
    const { sandbox } = loadAppScript();
    const el = {};
    sandbox.$.__setText(el, JSON.stringify({ root_path: "/nw", crossword_id: 42 }));
    sandbox.$(el).data("scope", "window");
    sandbox.$.__registerElements("script.data-transfer", [el]);

    sandbox.transferData();

    assert.equal(sandbox.window.root_path, "/nw");
    assert.equal(sandbox.window.crossword_id, 42);
});

test("transferData(): with no data-scope attribute at all, still defaults to window", () => {
    const { sandbox } = loadAppScript();
    const el = {};
    sandbox.$.__setText(el, JSON.stringify({ currentUser: 7 }));
    // deliberately not calling .data("scope", ...) - mirrors a <script> tag with no data-scope attribute
    sandbox.$.__registerElements("script.data-transfer", [el]);

    sandbox.transferData();

    assert.equal(sandbox.window.currentUser, 7);
});

test("transferData(): a named scope is created as an object and populated instead of polluting window directly", () => {
    const { sandbox } = loadAppScript();
    const el = {};
    sandbox.$.__setText(el, JSON.stringify({ apiKey: "abc", timeout: 10 }));
    sandbox.$(el).data("scope", "myApp");
    sandbox.$.__registerElements("script.data-transfer", [el]);

    sandbox.transferData();

    assert.equal(sandbox.window.apiKey, undefined, "must not leak into window when scoped");
    assert.deepEqual({ ...sandbox.window.myApp }, { apiKey: "abc", timeout: 10 });
});

test("transferData(): multiple data-transfer blocks on one page are all applied", () => {
    const { sandbox } = loadAppScript();
    const el1 = {};
    const el2 = {};
    sandbox.$.__setText(el1, JSON.stringify({ root_path: "/nw" }));
    sandbox.$.__setText(el2, JSON.stringify({ crossword_id: 5 }));
    sandbox.$.__registerElements("script.data-transfer", [el1, el2]);

    sandbox.transferData();

    assert.equal(sandbox.window.root_path, "/nw");
    assert.equal(sandbox.window.crossword_id, 5);
});
