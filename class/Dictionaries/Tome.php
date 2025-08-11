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

    class Tome extends Model {
        public string $name;
        public ?string $source = null;
        public ?string $source_type = null;
        public string $source_format;
        public int $readable = 0;
        public int $writeable = 0;
        public int $user_id;
        public ?string $last_updated = null;

        static string $tableName = "tomes";
        static $fields = ['id','name','source','source_type','source_format','readable','writeable','user_id','last_modified','created','modified'];

        public static $defaultOrderBy = ['id'];

        // Relationships
        public static $belongsTo = User::class;
        public static $hasMany = [TomeEntry::class];

        public const TYPE_URL = 'url';
        public const TYPE_LOCAL = 'local';
        
        public const FORMAT_UNKNOWN = 'unknown';
        public const FORMAT_JSON = 'json';
        public const FORMAT_TEXT = 'text';

        public const PERMISSION_NOBODY = 0;
        public const PERMISSION_OWNER = 1;
        public const PERMISSION_PUBLIC = 2;

        /**
         * Constructs the class, initialising some values that can't be null - do not use except temporarily e.g. when unserializing a class
         */
        public function __construct()
        {
            // Set variables that can't be null
            $this->user_id = 0;
            $this->source_format = self::FORMAT_UNKNOWN;
        }

        /**
         * Retrieves all Tomes that are visible to the specified user
         * @param int $user_id the id of the user by which to filter
         * @return mixed an array of Tome objects
         */
        public static function getAllForUser(int $user_id) : array {
            // Get all tomes that the user owns and are not invisible
            $tomes_owned = Tome::find([['user_id','=',$user_id],['readable','!=',Tome::PERMISSION_NOBODY]]);
            // Get all tomes that are public-readable and not owned by user
            $tomes_readable = Tome::find([['user_id','!=',$user_id],['readable','=',Tome::PERMISSION_PUBLIC]]);
            $tomes_all = array_merge($tomes_owned,$tomes_readable);
            return $tomes_all;
        }

        /**
         * Retrieves the last_modified field as a nullable DateTime
         * @return ?DateTime the last_modified date
         */
        public function getLastUpdated():?DateTime {
            // TODO - HIGH - needs casting, surely?
            return $this->last_updated;
        }

        /**
         * Updates the last_modified field to be the maximum of created/modified from all its TomeEntries, or its own created field if there are no entries
         * NB - we don't want to run this on TomeEntry save as it would run multiple times on bulk inserts, which is unnecessary.
         */
        public function updateLastModified() {
            // TODO - implement this - should be easy SQL
        }

        /**
         * Returns whether the specified tome can be read by the specified user
         * @param int $tome_id the id of the tome
         * @param int $user_id the id of the user to check
         * @return bool true if the tome is readable by that user; false if not
         */
        public static function readableBy(int $tome_id, int $user_id) : bool {
            // Check values
            if (!is_integer($tome_id)) { throw new Exception("Supplied tome_id {$tome_id} is not an integer."); }
            if (!is_integer($user_id)) { throw new Exception("Supplied user_id {$user_id} is not an integer."); }
            // Construct SQL
            $owner_readable = self::PERMISSION_OWNER;
            $public_readable = self::PERMISSION_PUBLIC;
            $sql = <<<END_SQL
SELECT COUNT(id)>0 AS readable
FROM tomes t
WHERE t.id=?
AND ((t.user_id = ? AND readable = {$owner_readable}) OR readable = {$public_readable})
END_SQL;
            $pdo = db::getPDO();
            $stmt = $pdo->prepare($sql);
            $criteria_values = [$tome_id,$user_id];
            $stmt->execute($criteria_values);
// TODO - HIGH Uncaught ArgumentCountError: PDOStatement::setFetchMode() expects exactly 2 arguments for the fetch mode provided, 1 given in C:\Bitnami\wampstack-8.0.9-0\apache2\htdocs\neverer-web\class\Dictionaries\Tome.php:109
            $stmt->setFetchMode(PDO::FETCH_COLUMN);
            $readable = $stmt->fetch();
            return ($readable == 1);
        }

    }
}