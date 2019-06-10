<?php

class SiteAdmin extends DB\SQL\Mapper{

    public function __construct(DB\SQL $db) {
        parent::__construct($db,'w_users');
    }

    public function all() {
        $this->load();
        return $this->query;
    }

    public function getById($id) {
        $this->load(array('userID=?',$id));
        return $this->query;
    }

    public function getByEmail($email) {
        $this->load(array('userEmail=?', $email));
    }

    public function add() {
        $this->copyFrom('POST');
        $this->save();
    }

    public function edit($id) {
        $this->load(array('userID=?',$id));
        $this->copyFrom('POST');
        $this->update();
    }

    public function delete($id) {
        $this->load(array('userID=?',$id));
        $this->erase();
    }
}