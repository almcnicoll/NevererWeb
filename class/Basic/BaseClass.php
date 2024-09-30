<?php
namespace Basic {
    /** Contains a few utility functions which are needed in all autoloaded classes */
    class BaseClass {
        /** Call this to ensure that the class is loaded by autoloader. It performs no action and has no other function. */
        public static function ensureLoaded() : void {
            return;
        }

        /**
         * Access the object in a JSON-encodable form
         */
        public function expose() : mixed {
            return get_object_vars($this);
        }

        /** Used for temporary tagging of objects in a way that isn't persisted to the database */
        public $__tag;
    }
}