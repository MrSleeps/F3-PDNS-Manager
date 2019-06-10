<?php

class Logins extends DB\SQL\Mapper{

    public function __construct(DB\SQL $db) {
        parent::__construct($db,'w_logins');
    }

    public function all() {
        $this->load();
        return $this->query;
    }

    public function getById($id) {
        $this->load(array('id=?',$id));
        return $this->query;
    }

    public function add($id,$ip,$agent,$masterid) {
		$ip = inet_pton ($ip);
        $this->loginUserID = $id;
		$this->loginIP = $ip;
		$this->loginAgent = $agent;
		$this->loginDate=date("Y-m-d H:i:s");
		$this->masterAccount=$masterid;
        $this->save();
		return $this->id;
    }

	public function getByMaster($masterid) {
        return $this->db->exec('Select w_logins.loginDate, w_logins.loginIP, w_users.userEmail From w_logins Inner Join w_users On w_logins.loginUserID = w_users.userID Where w_logins.masterAccount=? ORDER BY w_logins.loginDate DESC LIMIT 10',$masterid);
	}
	
}