"use strict";
const test = require("node:test");
const assert = require("node:assert/strict");
const { loadMasterScript } = require("./helpers");

function fakeJqXHR({ done, fail } = {}) {
    const xhr = {
        done(cb) {
            if (done !== undefined) cb(done);
            return xhr;
        },
        fail(cb) {
            if (fail) cb();
            return xhr;
        },
    };
    return xhr;
}

test("getDefinition: formats up to 3 meanings as '<em>pos</em>: definition' lines", async () => {
    const { sandbox } = loadMasterScript();
    const calls = [];
    sandbox.$.getJSON = (url) => {
        calls.push(url);
        return fakeJqXHR({
            done: [
                {
                    meanings: [
                        { partOfSpeech: "noun", definitions: [{ definition: "a small domesticated feline" }] },
                        { partOfSpeech: "verb", definitions: [{ definition: "to move stealthily" }] },
                    ],
                },
            ],
        });
    };

    const html = await new Promise((resolve) => sandbox.dictionary.getDefinition("CAT", resolve));

    assert.match(html, /<em>noun<\/em>: a small domesticated feline/);
    assert.match(html, /<em>verb<\/em>: to move stealthily/);
    assert.equal(calls.length, 1);
    assert.ok(calls[0].endsWith("/entries/en/cat"), `expected lowercase word in URL, got ${calls[0]}`);
});

test("getDefinition: caches by lowercase word and does not re-fetch", async () => {
    const { sandbox } = loadMasterScript();
    let fetchCount = 0;
    sandbox.$.getJSON = () => {
        fetchCount++;
        return fakeJqXHR({ done: [{ meanings: [{ partOfSpeech: "noun", definitions: [{ definition: "x" }] }] }] });
    };

    const first = await new Promise((resolve) => sandbox.dictionary.getDefinition("Dog", resolve));
    const second = await new Promise((resolve) => sandbox.dictionary.getDefinition("dog", resolve));

    assert.equal(first, second);
    assert.equal(fetchCount, 1, "second lookup (different case, same word) should be served from cache");
});

test("getDefinition: empty meanings and API failure both fall back to 'No definition found.'", async () => {
    const { sandbox } = loadMasterScript();
    sandbox.$.getJSON = (url) => (url.includes("nomeanings") ? fakeJqXHR({ done: [] }) : fakeJqXHR({ fail: true }));

    const emptyResult = await new Promise((resolve) => sandbox.dictionary.getDefinition("nomeanings", resolve));
    const failResult = await new Promise((resolve) => sandbox.dictionary.getDefinition("doesnotexist", resolve));

    assert.equal(emptyResult, "No definition found.");
    assert.equal(failResult, "No definition found.");
});

test("initDefinitionPopovers: mouseenter handler runs without throwing and uses the vanilla bootstrap.Popover API", () => {
    const { sandbox } = loadMasterScript();
    sandbox.$.getJSON = () => fakeJqXHR({ done: [{ meanings: [{ partOfSpeech: "noun", definitions: [{ definition: "a small domesticated feline" }] }] }] });

    sandbox.dictionary.initDefinitionPopovers();

    const enter = sandbox.$.__delegated.find((d) => d.event === "mouseenter" && d.selector === "td.suggested-word-list-item");
    assert.ok(enter, "should register a delegated mouseenter handler on td.suggested-word-list-item");

    const fakeCell = {}; // stand-in for a DOM <td> element
    sandbox.$.__setText(fakeCell, "CAT");

    // This is the crux of the regression: the original implementation called the
    // jQuery-Bootstrap-4-style `$td.popover(...)` / `$td.data("bs.popover")`, which don't
    // exist on plain jQuery + vanilla bootstrap.bundle.min.js (this app's actual setup -
    // see the <script> tags in index.php and the `new bootstrap.Modal(...)` usage
    // elsewhere in crossword_edit.js/crossword_solve.js). Calling it throws a TypeError.
    assert.doesNotThrow(() => enter.handler.call(fakeCell), /is not a function/);

    const popover = sandbox.bootstrap.__instances.get(fakeCell);
    assert.ok(popover instanceof sandbox.bootstrap.Popover, "should create a real bootstrap.Popover instance");
    assert.equal(popover._config.trigger, "manual");

    // getDefinition's callback should end up writing into the popover body that show() created
    assert.match(popover.tip._body.innerHTML, /a small domesticated feline/);
});

test("initDefinitionPopovers: mouseleave hides the existing popover instance via the vanilla API", () => {
    const { sandbox } = loadMasterScript();
    sandbox.$.getJSON = () => fakeJqXHR({ done: [] });
    sandbox.dictionary.initDefinitionPopovers();

    const enter = sandbox.$.__delegated.find((d) => d.event === "mouseenter");
    const leave = sandbox.$.__delegated.find((d) => d.event === "mouseleave");
    const fakeCell = {};
    sandbox.$.__setText(fakeCell, "DOG");
    enter.handler.call(fakeCell);

    const popover = sandbox.bootstrap.__instances.get(fakeCell);
    let hidden = false;
    popover.hide = () => {
        hidden = true;
    };

    assert.doesNotThrow(() => leave.handler.call(fakeCell));
    assert.ok(hidden, "mouseleave should call the popover instance's hide()");
});
