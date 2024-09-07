<?php

namespace Security {
    use Basic\Model;
    class Password extends Model {
        public int $user_id;
        public string $hash;

        static string $tableName = "passwords";
        static $fields = ['id','user_id','hash','created','modified'];

        public static $defaultOrderBy = [
            ['created','DESC'],
        ];

        public function setUser_id($id) {
            $this->user_id = $id;
        }

        public function getUser() : ?User {
            return User::getById($this->user_id);
        }
    }
}