<?php

class Records extends DB\SQL\Mapper
{
    
    public function __construct(DB\SQL $db)
    {
        parent::__construct($db, 'records');
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
    
    public function add()
    {
        $this->copyFrom('POST');
        $this->save();
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
    
    public function countRecords()
    {
        return count($this->all());
    }
    
    public function countRecordsByDomainID($domainid)
    {
        return count($this->load(array(
            'id=?',
            $id
        )));
    }
    
    public function getDomainRecords($domainid)
    {
        $this->load(array(
            'type!=? AND domain_id=?',
            'SOA',
            $domainid
        ), array(
            'order' => 'type ASC, prio ASC'
        ));
        return $this->query;
    }
    
    public function addSOA($domainid, $soacontent, $name, $type, $ttl, $priority)
    {
        $this->domain_id = $domainid;
        $this->name      = $name;
        $this->type      = $type;
        $this->content   = $soacontent;
        $this->ttl       = $ttl;
        $this->prio      = $priority;
        $this->save();
        return $this->id;
    }
    
    public function addNewHost($domainid, $domainname, $recordtype, $recordcontent, $recordpriority, $recordttl)
    {
        $this->domain_id = $domainid;
        $this->name      = $domainname;
        $this->type      = $recordtype;
        $this->content   = $recordcontent;
        $this->ttl       = $recordttl;
        $this->prio      = $recordpriority;
        $this->save();
        return $this->id;
    }
    
    public function deleteHost($recordid, $recordtype, $recordname, $recordcontent, $domainid)
    {
        $this->load(array(
            'id=? AND type=? AND name=? AND content=? AND domain_id=?',
            $recordid,
            $recordtype,
            $recordname,
            $recordcontent,
            $domainid
        ));
        if ($this->dry()) {
            return "error";
        } else {
            $this->erase();
            return "deleted";
        }
    }
    
    public function updateSOA($domainId, $soaprimary, $soaemail, $soaserial, $soarefresh, $soaretry, $soaexpire, $soattl)
    {
        $soa = new SOA($this->db);
        $this->load(array(
            'type=? AND domain_id=?',
            'SOA',
            $domainId
        ));
        $content = explode(" ", $this->content);
        $serial  = $content[2];
        $newsoa  = $soaprimary . " ";
        $newsoa .= $soa->mailToSOA($soaemail) . " ";
        $newsoa .= $soaserial . " ";
        $newsoa .= $soarefresh . " ";
        $newsoa .= $soaretry . " ";
        $newsoa .= $soaexpire . " ";
        $newsoa .= $soattl;
        $this->content = $newsoa;
        $this->save();
        return $this->id;
    }
    
    public function updateSerial($domainId)
    {
        $this->load(array(
            'type=? AND domain_id=?',
            'SOA',
            $domainId
        ));
        $content               = explode(" ", $this->content);
        $serial                = $content[2];
        $currentSerialDate     = (int) ($serial / 100);
        $currentSerialSequence = $serial % 100;
        $currentDate           = (int) date("Ymd");
        if ($currentDate != $currentSerialDate) {
            $newSerial = $currentDate . "00";
        } else {
            $newSerialSequence = ($currentSerialSequence + 1) % 100 . "";
            $newSerialSequence = str_pad($newSerialSequence, 2, "0", STR_PAD_LEFT);
            $newSerial         = $currentDate . "" . $newSerialSequence;
        }
        $content[2]    = $newSerial;
        $newsoa        = implode(" ", $content);
        $this->content = $newsoa;
        $this->save();
        $updatedserial = explode(" ", $newsoa);
        return $updatedserial[2];
    }
    
    public function countByType()
    {
        return $this->db->exec('select type, count(*) as amount from records where type is not null group by type');
    }
    
}