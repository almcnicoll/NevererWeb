<?php
namespace Security {
    use Basic\Model;
    use Crosswords\Crossword;
    use Exception;

    class User extends Model {
        public int $authmethod_id;
        public string $identifier;
        public ?string $email;
        public ?string $display_name;
        public ?string $image_url = null;

        static string $tableName = "users";
        static $fields = ['id','authmethod_id','identifier','email','display_name','image_url','created','modified'];

        public static $defaultOrderBy = [
            ['created','DESC'],
            ['display_name','ASC'],
        ];

        // Relationships
        public static $hasMany = Crossword::class;

        public function setAuthmethod_id($id) {
            $this->authmethod_id = $id;
        }

        public function getAuthmethod() : ?AuthMethod {
            return AuthMethod::getById($this->authmethod_id);
        }

        public function getPassword() : ?Password {
            /** @var Password $pwd */
            $pwd = Password::findFirst(
                ['user_id', '=', $this->id],
            );
            return $pwd;
        }

        public function getPasswordHash() : ?string {
            $password = Password::findFirst(
                ['user_id', '=', $this->id],
            );
            if ($password === null) { return null; }
            return $password->hash;
        }

        public function getThumbnail() : string {
            if (empty($this->image_url)) {
                // return initial
                $html = "<div class='initial-display'>".substr($this->display_name,0,1)."</div>";
            } else {
                // return pic
                $html = "<div class='initial-display'><img src='{$this->image_url}' /></div>";
            }
            return $html;
        }

        public function createPassword($supplied_password) : void {
            $authMethod = $this->getAuthmethod();
            if ($authMethod===null) { throw new Exception("No authentication method specified for current user"); }
            switch (strtolower($authMethod->methodName)) {
                case 'neverer':
                    $password = new Password();
                    $password->user_id = $this->id;
                    $password->hash = password_hash($supplied_password, PASSWORD_DEFAULT, []);
                    $password->save();
                    break;
                default:
                    throw new Exception("Cannot create a password for user with authentication method of '{$authMethod->methodName}'");
            }
        }

        public function checkPassword($supplied_password) : bool {
            $authMethod = $this->getAuthmethod();
            if ($authMethod===null) { throw new Exception("No authentication method specified for current user"); }
            switch ($authMethod->methodName) {
                case 'neverer':
                    $password = $this->getPassword();
                    if ($password === null) { throw new Exception("Could not find password for user."); }
                    if ($password->hash === null) { throw new Exception("Could not find password hash for user."); }
                    if (password_verify($supplied_password, $password->hash)) {
                        // Password is valid - see if it needs to be rehashed to come up-to-speed with latest hashing method
                        if (password_needs_rehash($password->hash, PASSWORD_DEFAULT, [])) {
                            $password->hash = password_hash($supplied_password, PASSWORD_DEFAULT, []);
                            $password->save();
                        }
                        return true;
                    } else {
                        return false;
                    }
                default:
                    throw new Exception("Cannot check password for user with authentication method of {$authMethod->methodName}");
            }
        }

        public static function loginCheck($redirectOnFail = true) : bool {
            $config = Config::get();

            // $login_check_redirect_on_fail allows pages to redirect unauthenticated users to custom URLs (e.g. / -> /nw/intro)
            // $login_check_soft_fail allows pages to refresh tokens if needed, but not redirect to login on fail (e.g. /nw/intro page, which is valid for unauthenticated users)
            if(session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

            $currentUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

            // Check if user is on developer dashboard list - we can lose this if we move to Production Mode
            $userCheckedOnList = (isset($_SESSION['USER_CHECKEDONLIST']) && ($_SESSION['USER_CHECKEDONLIST'] === true));

            // Determine if we have a valid session - need most USER vars and either ACCESS_TOKEN or REFRESH_TOKEN and REFRESHNEEDED
            $valid_session = true;
            $valid_session &= isset($_SESSION['USER']);
            $valid_session &= isset($_SESSION['USER_ID']);
            $valid_session &= isset($_SESSION['USER_AUTHMETHOD_ID']);
            /*$valid_session &= (
                isset($_SESSION['USER_ACCESSTOKEN'])
                ||
                (isset($_SESSION['USER_REFRESHTOKEN']) && isset($_SESSION['USER_REFRESHNEEDED']))
            );*/

            if(!$valid_session) {
                //error_log(__FILE__.':'.__LINE__." At least one session variable not set:\n".print_r($_SESSION,true));
                // Need to log in
                unset($_SESSION['USER']);
                //echo "<pre>Session:\n".print_r($_SESSION,true)."</pre>";
                if (($redirectOnFail !== false) && ($redirectOnFail !== 0) && ($redirectOnFail !== '0')) {
                    if (($redirectOnFail === true) || ($redirectOnFail === 1) || ($redirectOnFail === '1')) {
                        header("Location: {$config['root_path']}/login.php?redirect_url=".urlencode($currentUrl));
                        //file_put_contents('redirects.log',__LINE__." Location: {$config['root_path']}/login.php?redirect_url=".urlencode($currentUrl)."\n",FILE_APPEND);
                    } else {
                        header("Location: {$config['root_path']}{$redirectOnFail}");
                        //file_put_contents('redirects.log','Type: '.gettype($redirectOnFail)."\n",FILE_APPEND);
                        //file_put_contents('redirects.log',__LINE__." Location: {$config['root_path']}{$redirectOnFail}\n",FILE_APPEND);
                    }
                    die();
                }
                return false;
            } else {
                // Check if our token is still valid
                $refresh_needed = (int)($_SESSION['USER_REFRESHNEEDED']);
                //error_log(__FILE__.':'.__LINE__." refresh_needed = {$refresh_needed} // time() = ".time()." // Refresh if diff is negative: ".($refresh_needed-time()));
                //die("<pre>Comparing {$refresh_needed} to ".time()."</pre>\n");
                if ($refresh_needed < time()) {
                    // Call refresh mechanism
                    //error_log("Trying refresh");
                    $method = AuthMethod::getById((int)$_SESSION['USER_AUTHMETHOD_ID']);
                    header("Location: {$config['root_path']}/{$method->handler}?refresh_needed=true&redirect_url=".urlencode($currentUrl));
                    //file_put_contents('redirects.log',__LINE__." Location: {$config['root_path']}/{$method->handler}?refresh_needed=true&redirect_url=".urlencode($currentUrl)."\n",FILE_APPEND);
                    die();
                }
                
                // Otherwise, everything is OK! Just ensure that USER property is correctly populated as a User object
                $discard = new User(); // Ensure that User class is autoloaded
                $user = unserialize($_SESSION['USER']);
                $_SESSION['USER'] = serialize($user);
                return true;
            }
        }
    }
}