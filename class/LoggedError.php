<?php

use Error as GlobalError;

class LoggedError extends Model {

    const TYPE_PHP          =   'php';
    const TYPE_CURL         =   'curl';
    const TYPE_OTHER        =   'other';

    public string $type;
    public ?int $number = null;
    public ?string $file = null;
    public ?int $line = null;
    public ?string $message = null;

    static string $tableName = "errors";
    static $fields = ['id','type','file','line','number','message','created','modified'];

    public static $defaultOrderBy = [
        ['created','DESC'],
        ['id','ASC'],
    ];

    public function __construct($type, $number, $file, $line, $message) {
        $this->type = $type;
        $this->number = $number;
        $this->file = $file;
        $this->line = $line;
        $this->message = $message;
    }

    public static function log($type, $number, $file, $line, $message) : ?int {
        $error = new self($type, $number, $file, $line, $message);
        $error->save();
        return $error->id;
    }

}