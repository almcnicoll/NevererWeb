<?php
namespace Basic {
    /** Contains a few utility functions which are needed in all autoloaded classes */
    class BaseClass {
        /** Call this to ensure that the class is loaded by autoloader. It performs no action and has no other function. */
        public static function ensureLoaded() : void {
            return;
        }
    }
}