<?php

class AuthMethod extends Model {
    public string $methodName;
    public string $handler;
    public ?string $image;
    
    static string $tableName = "authmethods";
    static $fields = ['id','methodName','handler','image','created','modified'];
}