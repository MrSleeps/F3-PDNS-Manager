<?php

class DomainData extends DB\SQL\Mapper{

    public function __construct(DB\SQL $db) {
        parent::__construct($db,'w_domaindata');
    }

    public function all() {
        $this->load();
        return $this->query;
    }

    public function getById($id) {
        $this->load(array('id=?',$id));
        return $this->query;
    }

    public function getByDomain($domain) {
        $this->load(array('name=?', $domain));
		return $this->query;
    }
	
    public function getByDomainID($domainid) {
        $this->load(array('domainID=?', $domainid));
		return $this->query;
    }
		
    public function getByDomainAdmin($adminid) {
        $this->load(array('domainAdmin=?', $adminid));
		return $this->query;
    }

	public function getByDomainIDAndMaster($domainid,$masterid) {
		$this->load(array('domainID=? AND domainAdmin=?',$domainid,$masterid));
		return $this->query;		
	}
	
    public function add($domainid,$domainhash,$domainadminid) {
        $this->domainID = $domainid;
		$this->domainHash = $domainhash;
		$this->domainAdmin = $domainadminid;
        $this->save();
		return $this->id;
    }

    public function edit($id) {
        $this->load(array('id=?',$id));
        $this->copyFrom('POST');
        $this->update();
    }

    public function delete($id) {
        $this->load(array('id=?',$id));
        $this->erase();
    }
	
	public function countDomains() {
		return count($this->all());
	}

	public function countUserDomains($userid) {
		return count($this->getByDomainAdmin($userid));	
	}

	public function addDomainData($domainid, $domainname, $domainadminid) {
		$domainhash = password_hash($domainname, PASSWORD_DEFAULT);
		$domaindataid = $this->add($domainid,$domainhash,$domainadminid);
		if($domaindataid > 0) {
			return true;
		} else {
			return false;	
		}
	}
	
	public function deleteByDomainID($domainid) {
		$this->load(array('domainID=?',$domainid));
        $this->erase();
	}

	public function checkIsOwner($domainid,$userid) {
		$this->getByDomainIDAndMaster($domainid,$userid);	
		if($this->dry()) {
			return false;
		} else {
			return true;
		}
	}
	
}