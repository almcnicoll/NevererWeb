"use strict";
/**
 * Validates the Dexie schema version history in js/dict_worker.js, in particular the
 * uncommitted version(4) bump that restores a `tome_id` index on `entries`.
 *
 * parseTomeList() runs `db.entries.where("tome_id").equals(id).delete()` when pruning
 * entries for a tome that's disappeared server-side. Dexie throws if you `.where()` a
 * field that isn't declared as an index for the *current* schema version - it does not
 * silently fall back to a table scan. Version 2 dropped the `tome_id` index (keeping
 * only `id, word, bare_letters, length, [length+bare_letters]`) while adding it back
 * only in version 4, so versions 2-3 would have thrown here in production the first
 * time a subscribed tome was deleted/unsubscribed.
 */
const test = require("node:test");
const assert = require("node:assert/strict");
const { loadWorkerScript, parseIndexSpec } = require("./helpers");

test("schema versions are declared in increasing order with no gaps/dupes", () => {
    const { sandbox } = loadWorkerScript();
    const versions = sandbox.db.versions.map((v) => v.version);
    assert.deepEqual(versions, [1, 2, 3, 4]);
});

test("every declared version includes an entries schema with a primary key 'id'", () => {
    const { sandbox } = loadWorkerScript();
    for (const { version, schema } of sandbox.db.versions) {
        assert.ok(schema.entries, `version ${version} should define an entries store`);
        assert.equal(parseIndexSpec(schema.entries)[0], "id", `version ${version} entries PK should be 'id'`);
    }
});

test("versions 2-3 do NOT index tome_id on entries (the latent bug window)", () => {
    const { sandbox } = loadWorkerScript();
    const v2 = sandbox.db.versions.find((v) => v.version === 2);
    const v3 = sandbox.db.versions.find((v) => v.version === 3);
    assert.ok(!parseIndexSpec(v2.schema.entries).includes("tome_id"));
    assert.ok(!parseIndexSpec(v3.schema.entries).includes("tome_id"));
});

test("version 4 restores the tome_id index on entries", () => {
    const { sandbox } = loadWorkerScript();
    const v4 = sandbox.db.versions.find((v) => v.version === 4);
    assert.ok(v4, "version 4 should exist");
    assert.ok(parseIndexSpec(v4.schema.entries).includes("tome_id"), "version 4 entries should index tome_id");
});

test("simulated parseTomeList() cleanup: where('tome_id') throws under the v2/v3-shaped table, works under v4", () => {
    const { sandbox } = loadWorkerScript();
    // sandbox.db.entries currently reflects the LAST applied version's schema (v4, since
    // dict_worker.js applies all four in sequence) - this is the fixed, real end state.
    sandbox.db.entries.rows = [{ id: 1, tome_id: 42, word: "CAT" }];
    assert.doesNotThrow(() => sandbox.db.entries.where("tome_id").equals(42));

    // Now prove the *pre-fix* shape (v3's index list, no tome_id) really would have thrown -
    // this is what production was actually running before the uncommitted v4 change.
    const { FakeTable } = require("./helpers");
    const v3Shaped = new FakeTable("entries");
    v3Shaped.setSchema(sandbox.db.versions.find((v) => v.version === 3).schema.entries);
    assert.throws(() => v3Shaped.where("tome_id"), /not indexed/);
});
