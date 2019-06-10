<?php

class BigBrother extends DB\SQL\Mapper{

    public function __construct(DB\SQL $db) {
        parent::__construct($db,'w_logs');
    }

    public function all() {
        $this->load();
        return $this->query;
    }

    public function getById($id) {
        $this->load(array('id=?',$id));
        return $this->query;
    }

    public function addLogEntry($domainid,$domainname,$userid,$useremail,$action,$record,$masteraccountid) {
		if(empty($action)) { $action = $domainname; };
		$this->domainID=$domainid;
		$this->domainName=$domainname;
		$this->userID=$userid;
		$this->userEmail=$useremail;
		$this->action=$action;
		$this->record=$record;
		$this->masterID=$masteraccountid;		
        $this->save();
		return $this->id;
    }

	public function showLastTenMaster($masteraccountid) {
		return $this->db->exec('Select w_logs.action, w_logs.record, w_logs.domainID, w_logs.userID, w_logs.masterID, w_logs.domainName, w_logs.userEmail From w_logs Where w_logs.masterID = ? ORDER BY id ASC LIMIT 10',$masteraccountid);	
	}
}