<?php
/**
 * The clue itself, devoid of any crossword context.
 * It is contained within a PlacedClue class to connect it to a crossword.
 */
// TODO - HIGH - option to save explanations in database / model
namespace Dictionaries {
    use Basic\Model;
    use Override;

    class TomeEntry extends Model {
        public string $name;
        public ?int $tome_id = null;
        public string $word = '';
        public string $bare_letters = '';
        public ?int $length = null;
        public ?int $z = null; // For now, just use this one, as setting it to null is what flags the row as needing a letter-count update
        
        static string $tableName = "tome_entries";
        static $fields = ['id','tome_id','word','bare_letters','length','created','modified','z'];

        public static $defaultOrderBy = ['bare_letters','id'];

        // Relationships
        public static $belongsTo = Tome::class;
        public static $hasMany = [TomeClue::class];

        /**
         * Constructs the class, initialising some default values
         */
        public function __construct()
        {
        }

        /**
         * Flags the current entry as needing a letter-count refresh, which will populate fields `a` through `z` in the database
         */
        public function flagForLetterCountUpdate() {
            $this->z = null;
        }

        /**
         * Saves the entry the usual way, then updates the table's letter counts if needed
         */
        public function save() : ?int {
            $returnVal = parent::save();
            if ($this->z == null) {
                // We need to update letter count
                Tome::updateLetterCounts();
            }
            return $returnVal;
        }
    }
}