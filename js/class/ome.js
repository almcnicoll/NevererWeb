// js/class/Tome.js

/**
 * Represents a Tome (wordlist)
 */
class Tome {
  /**
   * @param {Object} data
   * @param {number} data.id
   * @param {string} data.name
   * @param {string} data.source_type
   * @param {string} data.source_format
   * @param {number} data.writeable
   * @param {number} data.user_id
   * @param {string|Date} data.last_updated
   */
  constructor({ id, name, source_type, source_format, writeable, user_id, last_updated }) {
    /** @type {number} */
    this.id = id;
    /** @type {string} */
    this.name = name;
    /** @type {string} */
    this.source_type = source_type;
    /** @type {string} */
    this.source_format = source_format;
    /** @type {number} */
    this.writeable = writeable;
    /** @type {number} */
    this.user_id = user_id;
    /** @type {Date} */
    this.last_updated = new Date(last_updated);
  }
}

self.Tome = Tome;
