"use strict";
/**
 * Shared test scaffolding for loading js/dict_worker.js and js/dict_master.js
 * into a Node vm sandbox, with just enough of a fake Dexie / jQuery / Bootstrap
 * surface for the scripts to load and run without a real browser.
 *
 * These are intentionally minimal stand-ins, not general-purpose reimplementations -
 * each only supports what the real scripts actually call.
 */
const fs = require("node:fs");
const path = require("node:path");
const vm = require("node:vm");

const JS_DIR = path.join(__dirname, "..", "..", "js");

/** Strips Dexie index modifiers (++ & *) so "id, ++foo, &bar" -> ["id","foo","bar"] */
function parseIndexSpec(schemaString) {
    return schemaString
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean)
        .map((s) => s.replace(/^[+&*]+/, ""));
}

class FakeTable {
    constructor(name) {
        this.name = name;
        this.indexes = new Set();
        this.rows = [];
    }
    setSchema(schemaString) {
        this.indexes = new Set(parseIndexSpec(schemaString));
    }
    /** Mirrors real Dexie: querying a non-indexed field throws (SchemaError), it doesn't table-scan. */
    where(field) {
        if (!this.indexes.has(field)) {
            throw new Error(`KeyPath ${field} on object store ${this.name} is not indexed`);
        }
        const table = this;
        return {
            equals(val) {
                const rows = table.rows.filter((r) => r[field] === val);
                return {
                    toArray: async () => rows.slice(),
                    delete: async () => {
                        const before = table.rows.length;
                        table.rows = table.rows.filter((r) => r[field] !== val);
                        return before - table.rows.length;
                    },
                };
            },
            equalsIgnoreCase(val) {
                const rows = table.rows.filter((r) => String(r[field] ?? "").toLowerCase() === String(val).toLowerCase());
                return { toArray: async () => rows.slice() };
            },
            between(lo, hi) {
                return { toArray: async () => table.rows.slice() };
            },
        };
    }
    toArray() {
        return Promise.resolve(this.rows.slice());
    }
    bulkPut(items) {
        this.rows.push(...items);
        return Promise.resolve();
    }
    bulkDelete(ids) {
        this.rows = this.rows.filter((r) => !ids.includes(r.id));
        return Promise.resolve();
    }
    put(item) {
        this.rows.push(item);
        return Promise.resolve(item.id);
    }
    get(key) {
        return Promise.resolve(this.rows.find((r) => r.id === key || r.key === key));
    }
}

class FakeDexie {
    constructor(name) {
        this.name = name;
        this.versions = []; // { version, schema }
        this._tables = new Map();
    }
    version(v) {
        const record = { version: v, schema: null };
        this.versions.push(record);
        const dexie = this;
        return {
            stores(schemaObj) {
                record.schema = schemaObj;
                for (const [tableName, spec] of Object.entries(schemaObj)) {
                    if (!dexie._tables.has(tableName)) {
                        dexie._tables.set(tableName, new FakeTable(tableName));
                        Object.defineProperty(dexie, tableName, {
                            get: () => dexie._tables.get(tableName),
                            enumerable: true,
                            configurable: true,
                        });
                    }
                    dexie._tables.get(tableName).setSchema(spec);
                }
                return dexie;
            },
        };
    }
    transaction(mode, ...args) {
        const fn = args[args.length - 1];
        return Promise.resolve().then(() => fn());
    }
}

/**
 * Loads js/dict_worker.js into a fresh vm context.
 * @returns {{sandbox: object, posted: Array<object>}}
 */
function loadWorkerScript() {
    const code = fs.readFileSync(path.join(JS_DIR, "dict_worker.js"), "utf8");
    const posted = [];
    const fakeSelf = {
        postMessage: (msg) => posted.push(msg),
        onmessage: null,
    };
    const sandbox = {
        self: fakeSelf,
        importScripts: () => {},
        Dexie: FakeDexie,
        console,
        setTimeout,
        clearTimeout,
        Date,
    };
    vm.createContext(sandbox);
    // `const db = ...` and `let latestAnagramRequestId = ...` are top-level lexical
    // bindings, not properties of the global object, so they wouldn't normally be
    // reachable from outside the script. Append a line (same lexical scope, so it can
    // still see them) that copies the ones tests need onto the sandbox object itself.
    // `anagramSearchTuning` is copied by reference, so tests CAN mutate its properties
    // (e.g. to force the yield path without needing a huge candidate set) even though
    // the binding itself is a const.
    vm.runInContext(code + "\nthis.db = db; this.anagramSearchTuning = anagramSearchTuning;\n", sandbox, {
        filename: "dict_worker.js",
    });
    return { sandbox, posted };
}

/** Minimal fake jQuery: deliberately has no .popover()/.modal()/.tooltip() - mirrors this app's
 *  real setup (jQuery 3.7 + vanilla bootstrap.bundle.min.js, no jQuery-Bootstrap plugin bridge). */
function makeFakeJQuery() {
    const dataStore = new Map(); // el (any object identity) -> {key: val}
    const delegated = []; // {event, selector, handler}
    const selectorElements = new Map(); // selector string -> array of fake elements
    function ensure(el) {
        if (!dataStore.has(el)) dataStore.set(el, {});
        return dataStore.get(el);
    }
    function wrap(el) {
        const self = {
            on(event, selector, handler) {
                if (typeof selector === "function") {
                    handler = selector;
                    selector = undefined;
                }
                delegated.push({ event, selector, handler });
                return self;
            },
            ready(fn) {
                $.__readyCallbacks.push(fn);
                return self;
            },
            text() {
                return ensure(el).__text ?? "";
            },
            data(key, val) {
                const store = ensure(el);
                if (val === undefined) return store[key];
                store[key] = val;
                return self;
            },
            html(val) {
                if (val === undefined) return ensure(el).__html ?? "";
                ensure(el).__html = val;
                return self;
            },
        };
        return self;
    }
    function wrapCollection(elements) {
        return {
            length: elements.length,
            each(fn) {
                elements.forEach((el, i) => fn.call(el, i, el));
                return this;
            },
        };
    }
    const $ = (selectorOrEl) => {
        if (typeof selectorOrEl === "string" && selectorElements.has(selectorOrEl)) {
            return wrapCollection(selectorElements.get(selectorOrEl));
        }
        return wrap(selectorOrEl);
    };
    $.getJSON = () => {
        throw new Error("$.getJSON must be stubbed per-test");
    };
    $.__delegated = delegated;
    $.__readyCallbacks = [];
    $.__setText = (el, text) => {
        ensure(el).__text = text;
    };
    /** Registers fake elements to be returned by $("<selector>"), for code that does $(selector).each(...) */
    $.__registerElements = (selector, elements) => {
        selectorElements.set(selector, elements);
    };
    return $;
}

/** Minimal fake of the vanilla (non-jQuery) Bootstrap 5 Popover class used elsewhere in this codebase. */
function makeFakeBootstrap() {
    const instances = new Map();
    class Popover {
        constructor(el, config) {
            this.el = el;
            this._config = { ...config };
            this.tip = null;
            instances.set(el, this);
        }
        show() {
            if (!this.tip) {
                const body = { innerHTML: "" };
                this.tip = { _body: body, querySelector: (sel) => (sel === ".popover-body" ? body : null) };
            }
        }
        hide() {}
        static getInstance(el) {
            return instances.get(el) || null;
        }
    }
    return { Popover, __instances: instances };
}

/**
 * Loads js/dict_master.js into a fresh vm context with fake Worker/jQuery/Bootstrap/makeAjaxCall.
 * @returns {{sandbox: object}}
 */
function loadMasterScript() {
    const code = fs.readFileSync(path.join(JS_DIR, "dict_master.js"), "utf8");
    class FakeWorker {
        constructor(scriptPath) {
            this.scriptPath = scriptPath;
            this.onmessage = null;
            this.posted = [];
        }
        postMessage(msg) {
            this.posted.push(msg);
        }
    }
    const sandbox = {
        $: makeFakeJQuery(),
        Worker: FakeWorker,
        bootstrap: makeFakeBootstrap(),
        root_path: "/nw",
        makeAjaxCall: () => {},
        document: {},
        console,
    };
    vm.createContext(sandbox);
    // `let dictionary = {}` is a top-level lexical binding, not a global-object property -
    // copy it onto the sandbox so tests can reach it (see loadWorkerScript for the same issue).
    vm.runInContext(code + "\nthis.dictionary = dictionary;\n", sandbox, { filename: "dict_master.js" });
    return { sandbox };
}

/**
 * vm.createContext() gives the sandbox its own realm, so arrays/objects built inside it
 * have a *different* Array.prototype/Object.prototype than ones built in the test file.
 * assert.deepStrictEqual treats that as a mismatch even when the contents are identical.
 * Round-tripping through JSON produces a plain object in the *caller's* realm.
 */
function toPlain(value) {
    return JSON.parse(JSON.stringify(value));
}

/** Waits for pending microtasks (promise chains) to drain, not just one tick's worth. */
function flush() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}

/** Repeatedly flushes (real setTimeout(0) ticks) until predicate() is true or maxTicks is reached. */
async function flushUntil(predicate, maxTicks = 50) {
    for (let i = 0; i < maxTicks; i++) {
        if (predicate()) return true;
        await flush();
    }
    return predicate();
}

/**
 * Loads js/app.js into a fresh vm context. `window` is a self-reference (as it is in a
 * real browser's global scope) so `window.foo = 1` inside the script is observable as
 * sandbox.foo afterwards.
 * @returns {{sandbox: object}}
 */
function loadAppScript() {
    const code = fs.readFileSync(path.join(JS_DIR, "app.js"), "utf8");
    const sandbox = {
        $: makeFakeJQuery(),
        console,
    };
    sandbox.window = sandbox;
    vm.createContext(sandbox);
    vm.runInContext(code, sandbox, { filename: "app.js" });
    return { sandbox };
}

module.exports = {
    parseIndexSpec,
    FakeTable,
    FakeDexie,
    loadWorkerScript,
    loadMasterScript,
    loadAppScript,
    makeFakeJQuery,
    makeFakeBootstrap,
    toPlain,
    flush,
    flushUntil,
};
