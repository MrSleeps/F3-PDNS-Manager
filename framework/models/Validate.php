<?php

class Validate extends Controller
{
    
    public function __construct(DB\SQL $db)
    {
    }
    
    public function isValidIP($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function isValidEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function isValidDomainID($domainid, $db)
    {
        $filter_options = array(
            'options' => array(
                'min_range' => 0
            )
        );
        if (filter_var($domainid, FILTER_VALIDATE_INT, $filter_options) !== FALSE) {
            $domains = new Domains($db);
            $domains->getById($domainid);
            if ($domains->dry()) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
    
    public function isAsciiDomain($domain)
    {
        return idn_to_ascii($domain);
    }
    
    public function isAsciiEmail($email)
    {
        //$email = 'post@øl.no';
        list($user, $domain) = explode('@', $email);
        $user   = idn_to_ascii($user);
        $domain = idn_to_ascii($domain);
        $email  = $user . '@' . $domain;
        return $email;
    }
    
    public function showRealDomain($domain)
    {
        return idn_to_utf8($domain);
    }
    
    public function isValidDomain($domain)
    {
        if (preg_match('/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}/', $domain)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function isValidNumberG0($number)
    {
        if (!ctype_digit($number) && $number >= 1) {
            return false;
        } else {
            return true;
        }
    }
    
    public function isValidNumber($number)
    {
        if (!ctype_digit($number) && $number >= 0) {
            return false;
        } else {
            return true;
        }
    }
    
    public function isLoggedIn($f3, $db)
    {
        $authTokens = new AuthTokens($db);
        $authTokens->clearOldTokens();
        if (isset($_SESSION["adminlevel"])) {
            return true;
        } elseif (isset($_COOKIE["rememberMe"])) {
            $cookieString      = $_COOKIE["rememberMe"];
            $cookieStringSplit = explode(",", $cookieString);
            $userlevel         = new UserLevel($db);
            $checkTokens       = $authTokens->checkTokens($cookieStringSplit[0], $cookieStringSplit[1]);
            if ($checkTokens > 0) {
                $users = new Users($db);
                $users->getById($checkTokens);
                $_SESSION["email"]           = $users->userEmail;
                $_SESSION["realname"]        = $users->userName;
                $_SESSION["adminlevel"]      = $users->userAdminLevel;
                $_SESSION["adminleveldesc"]  = $userlevel->getLevelDesc($users->userAdminLevel);
                $_SESSION["maxdomains"]      = $users->userMaxDomains;
                $_SESSION["userid"]          = $users->userID;
                $_SESSION["masteraccountid"] = $users->userMasterAccount;
            } else {
                setcookie("rememberMe", "", -1, "/");
                // $f3->reroute('/login');
            }
        } else {
            $f3->reroute('/login');
        }
    }
    
    public function requiredLevel($requiredlevel)
    {
        $f3 = Base::instance();
        if ($f3->get('SESSION.adminlevel')) {
            $adminlevel = $f3->get('SESSION.adminlevel');
            if ($adminlevel = $requiredlevel) {
                return true;
            } else {
                return false;
            }
        }
    }
    
    public function genPassword($length = 15, $add_dashes = false, $available_sets = 'luds')
    {
        function tweak_array_rand($array)
        {
            if (function_exists('random_int')) {
                return random_int(0, count($array) - 1);
            } elseif (function_exists('mt_rand')) {
                return mt_rand(0, count($array) - 1);
            } else {
                return array_rand($array);
            }
        }
        
        $sets = array();
        if (strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if (strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if (strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if (strpos($available_sets, 's') !== false)
            $sets[] = '!@#$%&*?';
        $all      = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[tweak_array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[tweak_array_rand($all)];
        $password = str_shuffle($password);
        if (!$add_dashes)
            return $password;
        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while (strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }
}