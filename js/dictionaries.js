/**
 * The Dexie.js namespace.
 * @external "Dexie"
 * @see {@link https://cdnjs.cloudflare.com/ajax/libs/dexie/4.0.8/dexie.js}
 */

class Dictionaries {
    /*
    * Tables:
    * tomes: volumes of dictionary entries (e.g. sowpods)
    * entries: words or phrases that form valid answers
    * clues: possible questions (with or without explanations) which relate to a given word or phrase
    */


    /**
     * References the local database
     * @type {Dexie}
     */
    static db;

    /**
     * Contains all tomes that have been loaded
     * @type {Array}
     */
    static tomes = [];

    /**
     * Initialises dictionary and database functionality
     */
    static init() {
        //#region dexie_definitions
        Dictionaries.db = new Dexie('NevererWeb');
        Dictionaries.db.version(2).stores({
            sowpods: 'word, lettercount',
            tomes: '++id, name, source_type, source_format, source, writeable, last_updated',
            entries: '++id, tome_id, answer, answerletters, lettercount',
            clues: '++id, answer, clue, explanation'
        });
        //#endregion
        Dictionaries.ensureSowpods();
    }

    /**
     * 
     * @param {string} source the source URL to load from
     */
    static load(source) {
        //
    }

    //#region data-load

    /**
     * Ensures that the SOWPODS dictionary is loaded into indexeddb
     * @returns {void}
     */
    static async ensureSowpods() {
        // TODO - HIGH get this into a WebWorker thread - otherwise it blocks the UI for ages!
        var sowpodsDict;
        var sowpodsCount;
        var sowpodsDicts = await Dictionaries.db.tomes
                                .where("name").equalsIgnoreCase("SOWPODS").toArray();
        if (sowpodsDicts.length >= 1) {
            // Loaded - output length
            sowpodsDict = sowpodsDicts[0];
            sowpodsCount = await Dictionaries.db.entries.where("tome_id").equals(sowpodsDict.tome_id).count();
            debugPane.print("SOWPODS exists. Word count: "+sowpodsCount);
        } else {
            // Not loaded - load it now
            debugPane.print("SOWPODS tome doesn't exist in db");
            var tomeSowpods = Tome.load('url','JSON','../../files/sowpods.json');
        }
        
        $.get({url: root_path+'/files/sowpods.json'}).done(
            async function(data) {
                //Long list of words returned
                /** @type Array */
                var sowpods;
                if (typeof data == 'string') {
                    sowpods = JSON.parse(data);
                } else {
                    sowpods = data;
                }
                if(sowpods.length > sowpodsCount) {
                    // Need to load in more words
                    debugPane.print("Loading words from file.");
                    for(var i in sowpods) {
                        var obj = sowpods[i];
                        obj.lettercount = obj.word.length; //{word: sowpods[i], lettercount: sowpods[i].length};
                    }
                    Dictionaries.db.sowpods.bulkPut(sowpods);
                    sowpodsCount = await Dictionaries.db.entries.where("tome_id").equals(sowpodsDict.tome_id).count();
                    debugPane.print("New SOWPODS count: "+sowpodsCount);
                }
            }
        ).fail(function() {
            // TODO - something here
        });
    }
    //#endregion

}

/*
Structure of dictionaries object:
Dictionaries.tomes - Array of Tome objects

*/


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
$(document).ready(Dictionaries.init);
//#endregion
