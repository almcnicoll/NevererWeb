<?php

class DbUpdate extends Model {
    public int $version;

    static string $tableName = "dbupdates";
    static $fields = ['version'];

    public static function ensureTableExists() : bool {
        $pdo = db::getPDO();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $sql = "SHOW TABLES LIKE 'dbupdates';";
        $stmt = $pdo->query($sql, PDO::FETCH_ASSOC);
        if ($stmt->fetch()) { return false; } // We have a table
        $sql = <<<END_SQL
CREATE TABLE `dbupdates` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `version` int(10) unsigned NOT NULL,
    `created` datetime DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `IX_VERSION` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
END_SQL;
        $stmt = $pdo->query($sql);
        return true;
    }

    public static function highestVersion() :int {
        $pdo = db::getPDO();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $sql = "SELECT IFNULL(MAX(version),0) AS version FROM `".static::$tableName."`;";
        $stmt = $pdo->query($sql, PDO::FETCH_ASSOC);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($result === false) {
            return 0; // No rows
        } else {
            return (int)$result['version'];
        }
    }
}