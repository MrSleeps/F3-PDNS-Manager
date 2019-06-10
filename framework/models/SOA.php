<?php

class SOA extends DB\SQL\Mapper{

    public function __construct(DB\SQL $db) {
        parent::__construct($db,'records');
    }
	
	public function getSOADetails($domainid){
		$soadetails = $this->load(array('type=? AND domain_id=?','SOA',$domainid));
		$soainfo = explode(" ", $soadetails->content);
		return array(preg_replace('/\\.$/', "", $soainfo[0]),$this->soaMail($soainfo[1]),$soainfo[2],$soainfo[3],$soainfo[4],$soainfo[5],$soainfo[6]);
	}
	
	public function soaMail($soa) {
		$soamail = preg_replace('/([^\\\\])\\./', '\\1@', $soa, 1);
		$soamail = preg_replace('/\\\\\\./', ".", $soamail);
		$soamail = preg_replace('/\\.$/', "", $soamail);
		return $soamail;
	}

	public function mailToSOA($mail) {
		$parts = explode("@", $mail);		
		$parts[0] = str_replace(".", "\.", $parts[0]);
		$parts[] = "";
		return implode(".", $parts);
	}	
	
}