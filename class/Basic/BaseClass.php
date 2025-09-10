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

        /**
         * Access the object and all its descendants in a JSON-encodable form
         * Unless overridden, this will simply map to expose()
         */
        public function exposeTree() : mixed {
            $propertyName = '__type'; $this->{$propertyName} = get_class($this); // Set this property in a way that doesn't annoy the dev tools
            return $this->expose();
        }

        /** Used for temporary tagging of objects in a way that isn't persisted to the database */
        public $__tag;
    }
}