/**
 * Represents a collection of entries, each representing a word or phrase
 */
class Tome {
    /**
     * The database id of this object
     * @type {int}
     */
    id;
    /**
     * The name of the Tome
     * @type {string}
     */
    name;
    /**
     * The type of source from which the Tome is populated
     * values include 'file' (local file on server) and 'url' (remote URL)
     * @type {string}
     */
    source_type;
    /**
     * The format of source from which the Tome is populated
     * values include 'JSON'
     * @type {string}
     */
    source_format;
    /**
     * The file or URL from which the data is loaded
     * @type {string}
     */
    source;
    /**
     * Whether the source accepts writes as well as reads
     * @type {boolean}
     */
    writeable;
    /**
     * When the Tome was last updated from source
     * @type {Date}
     */
    last_updated;
    /**
     * The entries in the Tome
     * @type {TomeEntry[]}
     */
    entries;
    /**
     * Creates a new Tome
     * @param {int} id the database id
     * @param {string} name the name of the Tome
     * @param {string} source_type the type of source from which the Tome is loaded
     * @param {string} source the file or URL from which the Tome is loaded
     * @param {boolean} writeable whether the source accepts writes as well as reads
     * @param {Date} last_updated when the Tome was last updated from source
     * @param {TomeEntry[]} entries an array of TomeEntry objects 
     */
    constructor(id, name, source_type, source_format, source, writeable, last_updated, entries = []) {
        this.id = id;
        this.name = name;
        this.source_type = source_type;
        this.source_format = source_format;
        this.source = source;
        this.writeable = writeable;
        this.last_updated = last_updated;
        this.entries = entries;
    }

    
    /**
     * Loads tome data from the specified source
     * @param {string} source_type the type of source to load from (see source_type property for possible values) 
     * @param {string} source the file or URL from which to load
     * @returns {any} the specified data
     */
    static async retrieveData(source_type, source) {
        // TODO - add code for retrieving the data
        // TODO - HIGH create the success/failure functions, or find a way to handle them better than this (promises?)
        switch (source_type.toLowerCase()) {
            case 'url':
                makeAjaxCall('get', source, null, this.parseData, this.retrievalFailure);
                break;
            default:
                throw new Error("Don't know how to retrieve data from source of type "+source_type);
        }       
        
    }

    /**
     * Processes failure to load data
     */
    retrievalFailure() {
        //
    }

    /**
     * Parses the provided data and populates a Tome object with metadata, entries and clues
     * @param {string} source_format the format of the source data (see source_format property for possible values)
     * @param any} data the retrieved data 
     */
    async parseData(source_format, data) {
        // TODO - add code for parsing the data
        // TODO - HIGH we are apparently trying to store promise objects (or possibly other complex objects) in IndexedDB, which points to `data` being overly nested/structured. Check this.
        var dictObj;
        if (typeof data == 'string') {
            dictObj = JSON.parse(data);
        } else {
            dictObj = data;
        }

        this.id = dictObj.id;
        this.name = dictObj.name;
        this.source_type = dictObj.source_type;
        this.source = dictObj.source;
        this.writeable = dictObj.writeable;
        this.last_updated = dictObj.last_updated;
        this.entries = dictObj.entries;

        // Add tome
        //var tome_data = {id:Dictionaries.db.tomes.length+1, name: this.name, };
        var newTomeId = await Dictionaries.db.tomes.add(dictObj);

        // Add entries
        for (var i in this.entries) {
            if (typeof this.entries[i] == 'string') {
                this.entries[i] = new TomeEntry(this.entries[i]);
                this.entries[i].lettercount = this.entries[i].word.length;
            }
            this.entries[i].tome_id = newTomeId;
            //Dictionaries.tomes[Dictionaries.tomes.length] 
        }
        dictionary.db.entries.bulkPut(this.entries);
        var entryCount = await dictionary.db.entries.count();
        
        debugPane.print("New dictionary entry count: "+entryCount);
        
    }

    /**
     * Loads a Tome from the specified source
     * @param {string} source_type the type of source to load from (see source_type property for possible values) 
     * @param {string} source_format the format of the source data (see source_format property for possible values) 
     * @param {string} source the file or URL from which to load
     * @returns {Tome} the loaded Tome
     */
    static load(source_type, source_format, source) {
        var t = new Tome();
        t.source_format = source_format;
        
        // TODO - loading code here, including populating entries and clues
        // Pre-loading
        switch (source_type) {
            case 'url':
                break;
            default:
                throw new Error("Source of source_type "+source_type+" not supported.");
        }
        
        var data = Tome.retrieveData(source_type, source);
        t.parseData(t.source_format, data);

        return t;
        // TODO - I believe we can string the retrieval and parsing functions together with Promises
        /// using  .then() and .catch()
    }
}
/**
 * Represents an entry in a Tome
 */
class TomeEntry {
    /**
     * The database id of this object
     * @type {int}
     */
    id;
    /**
     * The letters (no spaces or punctuation) that make up the entry
     * @type {string}
     */
    answerLetters;
    /**
     * The answer to the clue, complete with spaces and punctuation
     * @type {string}
     */
    answer;
    /**
     * An array of TomeClue objects, each containing a question and optional explanation
     * @type {Array}
     */
    clues;
    /**
     * 
     * @param {string} answer the answer to the clue, complete with spaces and punctuation
     * @param {string} answerLetters the letters (no spaces or punctuation) that make up the entry - leave null to auto-calculate
     * @param {Array|null} clues (optional) an array of TomeClue objects representing questions for this answer
     */
    constructor(answer, answerLetters = null, clues=null) {
        if (typeof answer !== 'string') { throw new Error("answer must be a string"); }
        if ((answerLetters !== null) && (typeof answerLetters !== 'string')) { throw new Error("answerLetters must be a string or null"); }
        if ((clues !== null) && (clues.constructor !== Array)) { throw new Error("clues must be an array or null"); }
        this.answer = answer;
        this.answerLetters = answerLetters;
        this.clues = clues;
    }
}
class TomeClue {
    /**
     * The database id of this object
     * @type {int}
     */
    id;
    /**
     * The question for this clue
     * @type {string}
     */
    question;
    /**
     * The explanation of the question (for cryptic crosswords)
     * @type {string}
     */
    explanation;
    /**
     * 
     * @param {string} question the question for this clue
     * @param {string|null} explanation the explanation of the question (optional) 
     */
    constructor(question, explanation=null) {
        this.question = question;
        this.explanation = explanation;
    }
}