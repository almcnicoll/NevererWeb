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
    static retrieveData(source_type, source) {
        // TODO - add code for retrieving the data
        // TODO - HIGH create the success/failure functions, or find a way to handle them better than this (promises?)
        switch (source_type.toLowerCase()) {
            case 'url':
                makeAjaxCall('get', source, null, this.retrievalSuccess, this.retrievalFailure);
                break;
            default:
                throw new Error("Don't know how to retrieve data from source of type "+source_type);
        }       
        
    }

    /**
     * Parses the provided data and populates a Tome object with metadata, entries and clues
     * @param {string} source_format the format of the source data (see source_format property for possible values)
     * @param any} data the retrieved data 
     */
    static parseData(source_format, data) {
        // TODO - add code for parsing the data

        // SAMPLE CODE FROM ensureSowpods() - ignore the retrieval part
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
                    dictionary.db.sowpods.bulkPut(sowpods);
                    sowpodsCount = await dictionary.db.sowpods.count();
                    debugPane.print("New SOWPODS count: "+sowpodsCount);
                }
            }
        ).fail(function() {
            // TODO - something here
        });
    }

    /**
     * Loads a Tome from the specified source
     * @param {string} source_type the type of source to load from (see source_type property for possible values) 
     * @param {string} source_format the format of the source data (see source_format property for possible values) 
     * @param {string} source the file or URL from which to load
     * @returns {Tome} the loaded Tome
     */
    static load(source_type, source_format, source) {
        // TODO - loading code here, including populating entries and clues

        // TODO - move this code to retrieveData function
        // TODO - I believe we can then string the retrieval and parsing functions together with Promises
        /// using  .then() and .catch()
        // Retrieve data
        switch (source_type) {
            case 'file':
                break;
            case 'url':
                break;
            default:
                throw new Error("Source of source_type "+source_type+" not supported.");
        }
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