<?php

class Controller
{
    
    protected $f3;
    protected $db;
    
    function beforeroute()
    {
        
    }
    
    function afterroute()
    {

    }
    
    function __construct()
    {
        
        $f3       = Base::instance();
        $this->f3 = $f3;
        
        $db = new DB\SQL($f3->get('DB_DETS'), $f3->get('DB_USER'), $f3->get('DB_PSWD'), array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ));
        
        $this->db = $db;
        $this->f3 = $f3;
    }
    
}