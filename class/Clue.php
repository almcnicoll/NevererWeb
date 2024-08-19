<?php

class Clue extends Model {
    public int $placedclue_id;
    public ?string $question = null;
    public ?string $answer = null;
    public ?string $pattern = null;

    static string $tableName = "clues";
    static $fields = ['id','placedclue_id','question','answer','pattern','created','modified'];

    public static $defaultOrderBy = ['id'];
}