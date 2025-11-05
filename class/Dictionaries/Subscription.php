<?php
/**
 * The clue itself, devoid of any crossword context.
 * It is contained within a PlacedClue class to connect it to a crossword.
 */
namespace Dictionaries {
    use Basic\Model;
    use Basic\db;
    use DateTime;
    use Exception;
    use Security\User;
    use PDO;

    class Subscription extends Model {
        public int $user_id;
        public int $tome_id;
        public int $subscribed;

        static string $tableName = "subscriptions";
        static $fields = ['id','user_id','tome_id','subscribed','created','modified'];

        public static $defaultOrderBy = ['id'];

        // Relationships
        public static $hasOne = [User::class, Tome::class];

        public function __construct()
        {
        }

        public static function getAllForUser($user_id) {
            return Subscription::find(['user_id','=',$user_id]);
        }

        public function save($onDuplicateKeyUpdate = true) : ?int {
            return parent::save($onDuplicateKeyUpdate);
        }
    }
}