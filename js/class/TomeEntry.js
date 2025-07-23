// js/class/TomeEntry.js

/**
 * Represents a single word or phrase in a Tome
 */
class TomeEntry {
  /**
   * @param {Object} data
   * @param {number} data.tome_id
   * @param {string} data.word
   * @param {number} data.user_id
   * @param {string|Date} data.date_added
   */
  constructor({ tome_id, word, user_id, date_added }) {
    /** @type {number} */
    this.tome_id = tome_id;
    /** @type {string} */
    this.word = word;
    /** @type {number} */
    this.user_id = user_id;
    /** @type {Date} */
    this.date_added = new Date(date_added);
  }
}

self.TomeEntry = TomeEntry;
