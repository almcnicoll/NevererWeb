// js/class/TomeEntry.js

/**
 * Represents a single word or phrase in a Tome
 */
class TomeEntry {
  /**
   * @param {Object} data
   * @param {number} data.tome_id
   * @param {string} data.word
   * @param {string|null} data.bare_letters
   * @param {number} data.user_id
   * @param {string|Date} data.date_added
   */
  constructor({ tome_id, word, bare_letters, user_id, date_added }) {
    /** @type {number} */
    this.tome_id = tome_id;
    /** @type {string} */
    this.word = word;
    /** @type {string|null} */
    this.bare_letters = bare_letters || TomeEntry.computeBareLetters(word);
    /** @type {number} */
    this.length = bare_letters.length;
    /** @type {number} */
    this.user_id = user_id;
    /** @type {Date} */
    this.date_added = new Date(date_added);
  }

  /**
   * Returns a simplified version of the word: lowercase, stripped of accents and punctuation.
   * Useful for fuzzy or broad matching.
   * @param {string} word - The word to normalise.
   * @returns {string} The normalised version.
   */
  static computeBareLetters(word) {
    if (!word || typeof word !== 'string') return '';
    return word
      .normalize('NFD')                          // Split accented chars into base + diacritic
      .replace(/[\u0300-\u036f]/g, '')          // Remove diacritical marks
      .replace(/[^a-zA-Z]/g, '')                // Remove non-letter characters
      .toUpperCase();                           // Convert to uppercase
  }
}

self.TomeEntry = TomeEntry;