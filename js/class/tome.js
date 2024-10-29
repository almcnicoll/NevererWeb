/**
 * Represents a collection of entries, each representing a word or phrase
 */
class Tome {
    /**
     * The entries in the Tome
     * @type {TomeEntry}
     */
    entries;
    /**
     * Creates a new Tome
     * @param {Array} entries an array of TomeEntry objects 
     */
    constructor(entries = []) {
        this.entries = entries;
    }
}
/**
 * Represents an entry in a Tome
 */
class TomeEntry {
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