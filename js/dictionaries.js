let dictionary = {};

//#region database

dictionary.openRequest = indexedDB.open('neverer', 1);

dictionary.openRequest.onupgradeneeded = function(event) {
    // triggers if the client has no database or a lower version than the current one
    switch (event.oldVersion) {
        case 0:
            // Client had no database
            dictionary.db = dictionary.openRequest.result;
            dictionary.db.createObjectStore('words', {keyPath: 'id'});
        case 1:
            // Client was on v1 - upgrade to higher version
    }
}
dictionary.openRequest.onerror = function(event) {
    dictionary.enabled = false;
    console.error("Error", dictionary.openRequest.error);
}
dictionary.openRequest.onsuccess = function(event) {
    dictionary.enabled = true;
    dictionary.db = dictionary.openRequest.result;
    dictionary.db.onversionchange = dictionary.databaseVersionChanged;
}

dictionary.databaseVersionChanged = function(event) {
    // TODO - this happens if database is updated and a tab remains open with a connection to the old database
    // see "Parallel update problem" on https://javascript.info/indexeddb
}

//#endregion

//#region functions
//#endregion