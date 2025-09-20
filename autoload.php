<?php
ini_set("log_errors", 1);
ini_set('error_log','./_php_errors.log');
@session_start();
use Security\Config;
class Autoloader
{
    // Quick-fail these entries
    public static $ignore = [
        'Google\\Client',
        'Google\\Service\\Oauth2',
    ];

    public static function register()
    {
        global $config;
        spl_autoload_register(function ($class) {
            // Check our quick-fail list
            if (in_array($class, static::$ignore)) { return false; }
            
            // Go searching
            $file = 'class'.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
            if (file_exists($file)) {
                require $file;
                return true;
            } else {
                // Allow for us calling this one directory down (e.g. in ajax call)
                $file = '../'.$file;
                if (file_exists($file)) {
                    require $file;
                    return true;
                } else {
                    // Allow for us calling this two directories down
                    $file = '../'.$file;
                    if (file_exists($file)) {
                        require $file;
                        return true;
                    } else {
                        // Allow for us calling this three directories down
                        $file = '../'.$file;
                        if (file_exists($file)) {
                            require $file;
                            return true;
                        }
                        return false;
                    }
                }
            }
            return false;
        });
    }
}
Autoloader::register();

$config = Config::get(); // Retrieve config while we're here