<?php
/**
 * The clue itself, devoid of any crossword context.
 * It is contained within a PlacedClue class to connect it to a crossword.
 */
namespace Dictionaries {
    use Basic\Model;

    class TomeEntry extends Model {
        public string $name;
        public ?int $tome_id = null;
        public string $word = '';
        public string $bare_letters = '';

        static string $tableName = "tome_entries";
        static $fields = ['id','tome_id','word','bare_letters','created','modified'];

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


    }
}