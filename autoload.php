<?php
@session_start();
class Autoloader
{
    public static function register()
    {
        global $config;
        spl_autoload_register(function ($class) {
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