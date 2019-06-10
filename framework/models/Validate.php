<?php

class Validate extends Controller {

    public function __construct(DB\SQL $db) {
    }
	
	public function isValidIP($ip){
		if(filter_var($ip, FILTER_VALIDATE_IP)) {
		  return true;
		}
		else {
		  return false;
		}
	}

	public function isValidEmail($email) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return true;
		} else {
			return false;	
		}
	}

	public function isValidDomainID($domainid,$db) {
		$filter_options = array('options' => array( 'min_range' => 0));
		if(filter_var($domainid, FILTER_VALIDATE_INT, $filter_options ) !== FALSE) {
		   $domains = new Domains($db);
		   $domains->getById($domainid);
		   if($domains->dry()) {
		   		return false;
		   } else {
			return true;
		   }
		} else {
			return false;
		}
	}

	public function isAsciiDomain($domain) {
			return idn_to_ascii($domain);
	}
	
	public function isAsciiEmail($email) {
		//$email = 'post@øl.no';
		list($user, $domain) = explode('@', $email);
			$user = idn_to_ascii($user);
			$domain = idn_to_ascii($domain);	
			$email = $user . '@' . $domain;
			return $email;
	}

	public function showRealDomain($domain) {
		return idn_to_utf8($domain);	
	}

	public function isValidDomain($domain) {
		if (preg_match('/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}/', $domain)) {
			return true;
		} else {
			return false;	
		}
	}
	
	public function isValidNumberG0($number) {
		if(!ctype_digit($number) && $number >= 1) {
			return false;	
		} else {
			return true;
		}
	}

	public function isValidNumber($number) {
		if(!ctype_digit($number) && $number >= 0) {
			return false;	
		} else {
			return true;	
		}
	}

	public function isLoggedIn($f3,$db) {
		$authTokens = new AuthTokens($db);
		$authTokens->clearOldTokens();
		if(isset($_SESSION["adminlevel"])) {
			return true;
		} elseif (isset($_COOKIE["rememberMe"])) {
			$cookieString = $_COOKIE["rememberMe"];
			$cookieStringSplit = explode (",", $cookieString);
			$userlevel = new UserLevel($db);
			$checkTokens = $authTokens->checkTokens($cookieStringSplit[0],$cookieStringSplit[1]);
			if($checkTokens > 0) {
				$users     = new Users($db);
				$users->getById($checkTokens);
				$_SESSION["email"] = $users->userEmail;
				$_SESSION["realname"] = $users->userName;
				$_SESSION["adminlevel"] = $users->userAdminLevel;
				$_SESSION["adminleveldesc"] = $userlevel->getLevelDesc($users->userAdminLevel);
				$_SESSION["maxdomains"] = $users->userMaxDomains;
				$_SESSION["userid"] = $users->userID;
				$_SESSION["masteraccountid"] = $users->userMasterAccount;
			} else {
				setcookie("rememberMe", "", -1, "/");
				// $f3->reroute('/login');
			}
		} else {
			$f3->reroute('/login');
		}
	}
	
	public function requiredLevel($requiredlevel) {
		$f3 = Base::instance();
		if($f3->get('SESSION.adminlevel')) {
			$adminlevel = $f3->get('SESSION.adminlevel');
			if($adminlevel = $requiredlevel) {
				return true;	
			} else {
				return false;	
			}
		}
	}
	
	public function genPassword() {
		$alpha = "abcdefghijklmnopqrstuvwxyz";
		$alpha_upper = strtoupper($alpha);
		$numeric = "0123456789";
		$special = ".-+=_,!@$#*%<>[]{}";
		$chars = "";
		$chars = $alpha . $alpha_upper . $numeric . $special;
		$length = 9;
		$len = strlen($chars);
		$pw = '';
		for ($i=0;$i<$length;$i++)
				$pw .= substr($chars, rand(0, $len-1), 1);
		return str_shuffle($pw);	
	}
}