/**
 * The Dexie.js namespace.
 * @external "Dexie"
 * @see {@link https://cdnjs.cloudflare.com/ajax/libs/dexie/4.0.8/dexie.js}
 */
let dictionary = {};

//#region dexie_definitions
dictionary.db = new Dexie('NevererWeb');
dictionary.db.version(1).stores({
    sowpods: 'word, lettercount'
});
//#endregion

//#region data-load

// Ensure we at least have SOWPODS loaded
var sowpodsCount = dictionary.db.sowpods.count();
$.get({url: root_path+'/files/sowpods.json'}).done(
    function(data) {
        //Long list of words returned
        /** @type Array */
        var sowpods = JSON.parse(data);
        if(sowpods.length > sowpodsCount) {
            // Need to load in more words
            for(var i in sowpods) {
                var obj = sowpods[i];
                obj.lettercount = obj.word.length; //{word: sowpods[i], lettercount: sowpods[i].length};
                dictionary.db.sowpods.put(obj);
            }
        }
    }
);
//#endregion

//#region database
/*
dictionary.init = function() {
    dictionary.openRequest = indexedDB.open('neverer', 1);
    
    dictionary.createWordsStore = function() { dictionary.db.createObjectStore('words', {keyPath: 'id'}); };

    dictionary.openRequest.onupgradeneeded = function(event) {
        // triggers if the client has no database or a lower version than the current one
        switch (event.oldVersion) {
            case 0:
                // Client had no database
                dictionary.db = dictionary.openRequest.result;
                dictionary.createWordsStore();
            case 1:
                // Client was on v1 - upgrade to higher version
        }
    }
    dictionary.openRequest.onerror = function(event) {
        dictionary.enabled = false;
        debugPane.print(dictionary.openRequest.error, 'error');
        console.error("Error", dictionary.openRequest.error);
    }
    dictionary.openRequest.onsuccess = function(event) {
        dictionary.enabled = true;
        dictionary.db = dictionary.openRequest.result;
        debugPane.print("Database version: "+dictionary.db.version, 'info');
        debugPane.print("words store exists: "+dictionary.db.objectStoreNames.contains('words'), 'info');
        if (!dictionary.db.objectStoreNames.contains("words")) { dictionary.createWordsStore(); } // Include for clients that ran old v1
        // Check if we have sowpods loaded
        var store = db.transaction(['words'], 'readonly').objectStore('words');
        var count = store.count();
        count.onsuccess = function() {
            if(count.result < 267000) {  ; }
        }
        dictionary.db.onversionchange = dictionary.databaseVersionChanged;
    }

    dictionary.databaseVersionChanged = function(event) {
        // TODO - this happens if database is updated and a tab remains open with a connection to the old database
        // see "Parallel update problem" on https://javascript.info/indexeddb
    }
}
*/
//#endregion

//#region functions

//#endregion

//#region init
//$(document).ready(dictionary.init);
//#endregion
