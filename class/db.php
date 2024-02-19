<?php

class db {
    private static $pdo; 

    static function getDSN() {
        global $config;
        return "mysql:host={$config['DB_HOST']};dbname={$config['DB_SCHEMA']};charset={$config['DB_CHARSET']};port={$config['DB_PORT']}";
    }

    public static function getPDO() {
        global $config;
        if (isset(static::$pdo) && !empty(static::$pdo)) { return static::$pdo; }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $dsn = db::getDSN();
        static::$pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD'], $options);
        return static::$pdo;
    }
}