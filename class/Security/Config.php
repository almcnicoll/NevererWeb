<?php

namespace Security {
    use Basic\BaseClass;
    class Config extends BaseClass {
        private static $__config = [];
        private static $__loaded = false;

        private static function retrieveSecrets() {
            $config = [];
            if (!@include_once('inc/secret.php')) {
                if (!@include_once('../inc/secret.php')) {
                    if (!@include_once('../../inc/secret.php')) {
                        require_once('../../../inc/secret.php');
                    }
                }
            }
            self::$__config += $config;
        }

        private static function retrieveLocalConfig() {
            $config = [];
            if (!@include_once('inc/config.local.php')) {
                if (!@include_once('../inc/config.local.php')) {
                    if (!@include_once('../../inc/config.local.php')) {
                        @include_once('../../../inc/config.local.php');
                    }
                }
            }
            self::$__config += $config;
        }

        public static function init() {
            if (self::$__loaded) { return; }

            self::retrieveSecrets();
            self::retrieveLocalConfig();
            // Add any non-local, non-secret config here in the form:
            // self::$__config['variable_key'] = 'variable value';

            // When to perform user authentication on the specified pages
            //  values are PAGEAUTH_NONE (don't authenticate), PAGEAUTH_LATE (authenticate after content rendered) or PAGE_AUTH_EARLY (authenticate first thing)
            //  the default is 'early' and any unrecognised values will be handled as 'early'
            // TODO - LOW move this to separate Route/Page class?
            self::$__config['pageinfo'] = [];
            self::$__config['pageinfo']['index'] = new PageInfo(PageInfo::AUTH_EARLY, '/nw/intro');
            self::$__config['pageinfo']['nw_intro'] = new PageInfo(PageInfo::AUTH_NEVER, false);
            self::$__config['pageinfo']['nw_faq'] = new PageInfo(PageInfo::AUTH_NEVER, false);
            //self::$__config['pageinfo']['account_request'] = new PageInfo(PageInfo::AUTH_NEVER, false);
            self::$__config['pageinfo']['privacy_policy'] = new PageInfo(PageInfo::AUTH_NEVER, false);

            self::$__loaded = true; // Don't need to reinitialise
        }

        public static function get() {
            return self::$__config;
        }

        /**
         * Gets the value held in the specific key, returning
         * the default value if the key does not exist
         * @param string $key the key to look up
         * @param mixed $defaultValue the value to return if the key does not exist
         * @return mixed the value looked up, or the default value
         */
        public static function getValueOrDefault(string $key, mixed $defaultValue = null) {
            if (array_key_exists($key, self::$__config)) {
                return self::$__config[$key];
            } else {
                return $defaultValue;
            }
        }
    }
    Config::init();
}