<?php
/**
 * The clue itself, devoid of any crossword context.
 * It is contained within a PlacedClue class to connect it to a crossword.
 */
namespace Dictionaries {
    use Basic\Model;
    use DateTime;
    use Security\User;

    class TomeClue extends Model {
        public int $tomeentry_id;
        public int $user_id;
        public ?string $question = null;
        public bool $cryptic = true;

        static string $tableName = "tome_clues";
        static $fields = ['id','tomeentry_id','user_id','question','cryptic','created','modified'];

        public static $defaultOrderBy = ['id'];

        // Relationships
        public static $belongsTo = TomeEntry::class;
        public static $hasOne = [User::class];

        /**
         * Constructs the class, initialising some default values
         */
        public function __construct()
        {
        }


    }
}