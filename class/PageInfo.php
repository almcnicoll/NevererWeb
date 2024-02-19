<?php

class PageInfo {
    
    public const AUTH_NEVER = 0;
    public const AUTH_EARLY = 1;
    public const AUTH_LATE = 2;

    public int $authSetting = self::AUTH_EARLY;
    public $redirectOnFail = true; // Values: false (don't redirect - "soft fail"), true (redirect to login page), URL (string)

    public function __construct($authSetting = self::AUTH_EARLY, $redirectOnFail = true) {
        $this->authSetting = $authSetting;
        $this->redirectOnFail = $redirectOnFail;
    }

    public static function get($stub) : PageInfo {
        $config = Config::get();
        //var_dump($config['pageinfo']);
        //die();
        if (array_key_exists($stub, $config['pageinfo'])) {
            // We have page config for this page
            $pageinfo = $config['pageinfo'][$stub];
            return $pageinfo;
        } else {
            return new PageInfo();
        }
    }

    public function processRequestData() {
        $config = Config::get();
        // Handle any parameters that might affect the redirectOnFail URL
        // If redirect_url passed in, stash it in the session data
        if (array_key_exists('redirect_url', $_REQUEST)) {
            $_SESSION['redirect_url'] = $_REQUEST['redirect_url'];
            unset($_REQUEST['redirect_url']);
        }
        // If error_message passed in, append it to the redirectOnFail property so it gets passed on
        if (array_key_exists('error_message', $_REQUEST)) {
            if (($this->redirectOnFail === true) || ($this->redirectOnFail === 1) || ($this->redirectOnFail === '1')) {
                $this->redirectOnFail = $config['root_path'].'/login.php';
            }
            if (strpos($this->redirectOnFail,'?') === false) {
                $this->redirectOnFail .= ('?'.http_build_query(['error_message' => $_REQUEST['error_message']]));
            } else {
                $this->redirectOnFail .= ('&'.http_build_query(['error_message' => $_REQUEST['error_message']]));
            }
        }
    }
}