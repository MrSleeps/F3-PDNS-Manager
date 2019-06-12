<?php

class Domains extends DB\SQL\Mapper
{
    
    public function __construct(DB\SQL $db)
    {
        parent::__construct($db, 'domains');
    }
    
    public function all()
    {
        $this->load();
        return $this->query;
    }
    
    public function getById($id)
    {
        $this->load(array(
            'id=?',
            $id
        ));
        return $this->query;
    }
    
    public function getByDomain($domain)
    {
        $this->load(array(
            'name=?',
            $domain
        ));
        return $this->query;
    }
    
    public function add($domainname, $type)
    {
        $this->name = $domainname;
        $this->type = $type;
        $this->save();
        return $this->id;
    }
    
    public function addSlave($domainname, $primaryNS, $type)
    {
        $this->name   = $domainname;
        $this->master = $primaryNS;
        $this->type   = $type;
        $this->save();
        return $this->id;
    }
    
    public function edit($id)
    {
        $this->load(array(
            'id=?',
            $id
        ));
        $this->copyFrom('POST');
        $this->update();
    }
    
    public function delete($id)
    {
        $this->load(array(
            'id=?',
            $id
        ));
        $this->erase();
    }
    
    public function countDomains()
    {
        return count($this->all());
    }
    
    public function listAllDomains()
    {
        return $this->db->exec('Select D.id, D.name, D.type, Count(R.domain_id) As records From domains D Left Outer Join records R On D.id = R.domain_id Group By D.id, D.name, D.type Order By name');
    }
    
    public function listAllDomainsMaster($masterid)
    {
        return $this->db->exec('Select D.id, D.name, D.type, Count(R.domain_id) As records, w_domaindata.domainAdmin From domains D Left Outer Join records R On D.id = R.domain_id Inner Join w_domaindata On R.domain_id = w_domaindata.domainID Where w_domaindata.domainAdmin = ? Group By D.id, D.name, D.type, w_domaindata.domainAdmin Order By D.name', $masterid);
    }
    
    public function listAllDomainsUserID($userid)
    {
        return $this->db->exec('Select
    domains.id,
    domains.name,
    domains.master,
    domains.type,
    w_users.userID,
    w_users.userEmail,
    Count(records.id) As recordCount
From
    domains Inner Join
    w_domaindata On w_domaindata.domainID = domains.id Inner Join
    w_users On w_users.userID = w_domaindata.domainAdmin Inner Join
    records On records.domain_id = domains.id
Where
    w_users.userID=? 
Group By
    domains.id,
    domains.name,
    domains.master,
    domains.type,
    w_users.userID,
    w_users.userEmail', $userid);
    }
    
    public function countDomainTypes()
    {
        return $this->db->exec('select type, count(*) as amount from domains GROUP BY type');
    }
}