<?php

class FAQ extends Model {
    public string $question = '';
    public string $answer = '';
    public int $rank = 0;

    static string $tableName = "faqs";
    static $fields = ['id','question','answer','rank','created','modified'];

    public static $defaultOrderBy = ['rank','id'];
}