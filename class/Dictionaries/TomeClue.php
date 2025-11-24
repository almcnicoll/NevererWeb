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
        public int $tome_id;
        public int $user_id;
        public string $word = '';
        public string $question = '';
        public bool $cryptic = true;

        static string $tableName = "tome_clues";
        static $fields = ['id','tome_id','user_id','question','cryptic','created','modified'];

        public static $defaultOrderBy = ['id'];

        // Relationships
        public static $belongsTo = Tome::class;
        public static $hasOne = [User::class];

        /**
         * Constructs the class, initialising some default values
         */
        public function __construct()
        {
        }


    }
}