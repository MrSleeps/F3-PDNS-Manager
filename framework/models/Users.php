<?php

class Users extends DB\SQL\Mapper
{
    
    public function __construct(DB\SQL $db)
    {
        parent::__construct($db, 'w_users');
    }
    
    public function all()
    {
        $this->load();
        return $this->query;
    }
    
    public function getById($userID)
    {
        $this->load(array(
            'userID=?',
            $userID
        ));
        //return $this->query;
    }
    
    public function getByEmail($email)
    {
        $this->load(array(
            'userEmail=?',
            $email
        ));
    }
    
    public function add($useremail, $username, $userrole, $maxdomains, $password, $masteraccountid)
    {
        $this->userEmail      = $useremail;
        $this->userName       = $username;
        $this->userAdminLevel = $userrole;
        $this->userMaxDomains = $maxdomains;
        $this->userPassword   = password_hash($password, PASSWORD_DEFAULT);
        
        $this->userEnabled = "1";
        $this->save();
        $insertid = $this->userID;
        if ($userrole == "2") {
            $masteraccountid = "1";
        }
        $this->load(array(
            'userID=?',
            $insertid
        ));
        $this->userMasterAccount = $masteraccountid;
        $this->save();
        return $insertid;
    }
    
    public function edit($id)
    {
        $this->load(array(
            'userID=?',
            $id
        ));
        $this->copyFrom('POST');
        $this->update();
    }
    
    public function delete($id)
    {
        $this->load(array(
            'userID=?',
            $id
        ));
        $this->erase();
    }
    
    public function countUsers()
    {
        return count($this->all());
    }
    
    public function countUserUsers($masterid)
    {
        return $this->count(array(
            'userMasterAccount=?',
            $masterid
        ));
    }
    
    public function countAdmins()
    {
        return $this->count(array(
            'userAdminLevel=?',
            '2'
        ));
    }
    
    public function listAllUsers()
    {
        return $this->db->exec('Select
    w_users.userID,
    w_users.userEmail,
    w_users.userPassword,
    w_users.userName,
    w_users.userAdminLevel,
    w_users.userResetToken,
    w_users.userResetTokenExpires,
    w_users.userEnabled,
    w_users.userMaxDomains,
    w_users.userMasterAccount,
    w_userlevels.userLevelDesc,
    w_domaindata.domainAdmin,
    w_domaindata.domainMaxRecords,
    Count(w_domaindata.domainID) As domainCount
From
    w_users Inner Join
    w_userlevels On w_userlevels.userLevelID = w_users.userAdminLevel Left Join
    w_domaindata On w_domaindata.domainAdmin = w_users.userMasterAccount
Group By
    w_users.userID,
    w_users.userEmail,
    w_users.userPassword,
    w_users.userName,
    w_users.userAdminLevel,
    w_users.userResetToken,
    w_users.userResetTokenExpires,
    w_users.userEnabled,
    w_users.userMaxDomains,
    w_users.userMasterAccount,
    w_userlevels.userLevelDesc,
    w_domaindata.domainAdmin,
    w_domaindata.domainMaxRecords');
    }
    
    public function listAllUsersMasterAccount($masteraccountid)
    {
        return $this->db->exec('Select w_users.userID, w_users.userEmail, w_users.userName, w_users.userEnabled, w_users.userMaxDomains, w_userlevels.userLevelDesc From w_users Inner Join w_userlevels On w_users.userAdminLevel = w_userlevels.userLevelID WHERE w_users.userMasterAccount=?', $masteraccountid);
    }
    
    public function listAllEmails()
    {
        return $this->select('userID,userEmail', null, array(
            'order' => 'userEmail ASC'
        ));
    }
    
    public function listAllEmailsMasterAccount($masteraccountid)
    {
        return $this->db->exec('SELECT userID, userEmail, UserMasterAccount from w_users WHERE userMasterAccount=? ORDER BY userEmail ASC', $masteraccountid);
    }
    
    public function updateUser($userid, $useremail, $userrole, $username, $maxnumber, $userenabled, $usermasteraccount)
    {
        $users = new Users($this->db);
        $this->load(array(
            'userID=?',
            $userid
        ));
        $this->userEnabled       = $userenabled;
        $this->userName          = $username;
        $this->userMaxDomains    = $maxnumber;
        $this->userAdminLevel    = $userrole;
        $this->userEmail         = $useremail;
        $this->userMasterAccount = $usermasteraccount;
        $this->userEnabled       = $userenabled;
        $this->save();
        return $useremail;
    }
    
    public function updateUserPassword($userID, $passwordOne)
    {
        $this->load(array(
            'userID=?',
            $userID
        ));
        $this->userPassword = password_hash($passwordOne, PASSWORD_DEFAULT);
        $this->save();
        return true;
    }
    
    public function checkIsMaster($userID, $masterID)
    {
        $this->load(array(
            'userID=? AND userMasterAccount=?',
            $userID,
            $masterID
        ));
        if ($this->dry()) {
            return false;
        } else {
            return true;
        }
    }
    
    public function getMasterAccount($userID)
    {
        $this->load(array(
            'userID=?',
            $userID
        ));
        if ($this->dry()) {
            return "none";
        } else {
            return $this->userMasterAccount;
        }
    }
    
    public function countUserTypes()
    {
        return $this->db->exec('Select w_userlevels.userLevelDesc as type, count(w_userlevels.userLevelDesc) as amount From w_users Inner Join w_userlevels On w_users.userAdminLevel = w_userlevels.userLevelID Group By w_userlevels.userLevelDesc');
    }
    
    public function createResetToken()
    {
        return bin2hex(random_bytes(20));
    }
    
    public function updateUserResetToken($userEmail, $userResetToken, $userResetTokenExpire)
    {
        $this->load(array(
            'userEmail=?',
            $userEmail
        ));
        $this->userResetToken        = $userResetToken;
        $this->userResetTokenExpires = $userResetTokenExpire;
        $this->save();
    }
    
    public function resetUserPasswordViaToken($userResetToken, $userPassword)
    {
        $this->load(array(
            'userResetToken=?',
            $userResetToken
        ));
        if ($this->dry()) {
            return "false";
        } else {
            if (new DateTime() < new DateTime($this->userResetTokenExpires)) {
                $this->userResetToken        = NULL;
                $this->userResetTokenExpires = NULL;
                $this->userPassword          = password_hash($userPassword, PASSWORD_DEFAULT);
                $this->save();
                return "changed";
            }
        }
    }
    
    public function createUserHash($userEmail)
    {
        $dateTime = date('d-m-Y H:i:s');
        return password_hash($dateTime . $userEmail, PASSWORD_DEFAULT);
    }
    
    public function checkEmailExists($userEmail)
    {
        $this->load(array(
            'userEmail=?',
            $userEmail
        ));
        if ($this->dry()) {
            return 0;
        } else {
            return 1;
        }
    }
	
	public function returnUserEmail($userID)
	{
        $this->load(array(
            'userID=?',
            $userID
        ));
		return $this->userEmail;
	}
}