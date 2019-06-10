<?php

class AuthTokens extends DB\SQL\Mapper{

    public function __construct(DB\SQL $db) {
        parent::__construct($db,'w_authtokens');
    }
	
	public function generateAuthTokens($userID) {
		$selector = bin2hex(openssl_random_pseudo_bytes(12));
		$validator = bin2hex(openssl_random_pseudo_bytes(64));
		$validatorHash = hash('sha256', $validator);
		$cookieString = $selector.",".$validator;
		setcookie("rememberMe", $cookieString, time() + (86400 * 14), "/");
		$date = date_create();
		date_add($date, date_interval_create_from_date_string('14 days'));
		$dateExpire = date_format($date, 'Y-m-d H:i:s');
		$this->load(array('userID=?',$userID));
		if($this->dry()) {
			$this->selector = $selector;
			$this->hashedValidator = $validatorHash;
			$this->userID = $userID;
			$this->expires = $dateExpire;
			$this->save();
		} else {
			$this->selector = $selector;
			$this->hashedValidator = $validatorHash;
			$this->userID = $userID;
			$this->expires = $dateExpire;
			$this->save();
		} 
	}
	
	public function checkTokens($selector,$validator) {
		$validatorHash = hash('sha256', $validator);
		$this->load(array('selector=? AND hashedValidator=?',$selector,$validatorHash));	
		if($this->dry()) {
			return false;
		} else {
			$date = date_create();
			$now = date_format($date, 'Y-m-d H:i:s');			
			if($now < $this->expires) {
				return $this->userID;
			} else {
				return false;
			}
		}
	}
	
	public function clearOldTokens() {
		$date = date_create();
		$now = date_format($date, 'Y-m-d H:i:s');
		$this->db->exec('DELETE FROM w_authtokens WHERE expires < ?',$now);
	}
}