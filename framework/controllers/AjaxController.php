<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AjaxController extends Controller
{
    
    public function ajaxAddDomain($f3)
    {
        $template         = new Template;
        $domains          = new Domains($this->db);
        $records          = new Records($this->db);
        $users            = new Users($this->db);
        $soa              = new SOA($this->db);
        $domainData       = new DomainData($this->db);
        $logs             = new BigBrother($this->db);
        $validate         = new Validate($this->db);
        $postedData       = json_decode($f3->get('BODY'), true);
        $adminLevel       = $this->f3->get('SESSION.adminlevel');
        $userMaxAccounts  = $this->f3->get('SESSION.maxdomains');
        $domainName       = $postedData['name'];
        $postDomainEmail  = $postedData['mail'];
        $domainPrimary    = $postedData['primary'];
        $domainAdmin      = $postedData['adminID'];
        $domainAdminEmail = $postedData['adminEmail'];
        $domainRefresh    = $postedData['refresh'];
        $domainExpire     = $postedData['expire'];
        $domainRetry      = $postedData['retry'];
        $domainTTL        = $postedData['ttl'];
        $domainType       = $postedData['type'];
        if ($adminLevel == '2') {
            if ($domainType == "main") {
                if (empty($domainName)) {
                    $error = "Domain Name Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                if (empty($postDomainEmail)) {
                    $error = "Domain Email Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                if (empty($domainPrimary)) {
                    $error = "Domain Primary Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                if (empty($domainAdmin)) {
                    $error = "Admin Account Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                //Check if domain is already in the database
                $domains->getByDomain($domainName);
                if (!$domains->dry()) {
                    $error = "Domain Already Exists";
                    $this->returnError($error);
                    return;
                }
                // Check if admin account exists
                $users->getById($domainAdmin);
                if ($users->dry()) {
                    $error = "Admin Account Does Not Exist";
                    $this->returnError($error);
                    return;
                }
                
                // Check domain is not IDN, if so convert to puny, if not return original domain
                $domainName = $validate->isAsciiDomain($domainName);
                if ($validate->isValidDomain($domainName, $this->db) == false) {
                    $error = "Sorry, " . $domainName . " is not a Valid Domain Name";
                    $this->returnError($error);
                    return;
                }
                
                // Check Primary is not IDN, if so convert to puny, if not return original domain
                $domainPrimary = $validate->isAsciiDomain($domainPrimary);
                if ($validate->isValidDomain($domainPrimary, $this->db) === false) {
                    $error = "Primary is not a Valid Domain Name";
                    $this->returnError($error);
                    return;
                }
                
                // Check email is not IDN, if so convert to puny, if not return original email
                $domainemail = $validate->isAsciiEmail($postDomainEmail);
                if ($validate->isValidEmail($domainemail) === false) {
                    $error = $domainemail . " is not a Valid Email Address";
                    $this->returnError($error);
                    return;
                }
                
                if (!isset($error)) {
                    $soaData   = Array();
                    $soaData[] = $domainPrimary;
                    $soaData[] = $soa->mailToSOA($domainemail);
                    $soaData[] = date("Ymd") . "00";
                    $soaData[] = $domainRefresh;
                    $soaData[] = $domainRetry;
                    $soaData[] = $domainExpire;
                    $soaData[] = $domainTTL;
                    
                    $soaContent = implode(" ", $soaData);
                    $adddomain  = $domains->add($domainName, 'MASTER');
                    if ($adddomain > 0) {
                        $addsoa     = $records->addSOA($adddomain, $soaContent, $domainName, 'SOA', $domainTTL, '0');
                        $adddomhash = $domainData->addDomainData($adddomain, $domainName, $domainAdmin);
                        if ($addsoa >= 0 && $adddomhash === true) {
                            $logs->addLogEntry($adddomain, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'ADD', $domainName, $this->f3->get('SESSION.masteraccountid'));
                            header('Content-type: application/json');
                            echo json_encode(array(
                                'newID' => $adddomain
                            ));
                        } else {
                            $this->returnError("Something Went Wrong");
                            return;
                        }
                    } else {
                        $this->returnError("Something has gone wrong");
                        return;
                    }
                } else {
                    $this->returnError($error);
                    return;
                }
                $error = "";
            }
            
            if ($domainType == "slave") {
                if (empty($domainName)) {
                    $error = "Domain Name Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                if (empty($domainPrimary)) {
                    $error = "Domain Primary Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                if (empty($domainAdmin)) {
                    $error = "Admin Account Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                //Check if domain is already in the database
                $domains->getByDomain($domainName);
                if (!$domains->dry()) {
                    $error = "Domain Already Exists";
                    $this->returnError($error);
                    return;
                }
                // Check if admin account exists
                $users->getById($domainAdmin);
                if ($users->dry()) {
                    $error = "Admin Account Does Not Exist";
                    $this->returnError($error);
                    return;
                }
                // Check domain is not IDN, if so convert to puny, if not return original domain
                $domainName = $validate->isAsciiDomain($domainName);
                if ($validate->isValidDomain($domainName, $this->db) == false) {
                    $error = "Sorry, " . $domainName . " is not a Valid Domain Name";
                    $this->returnError($error);
                    return;
                }
                if ($validate->isValidIP($domainPrimary) == false) {
                    $error = "Sorry, your Name Server IP address appears to be invalid.";
                    $this->returnError($error);
                    return;
                }
                if (!isset($error)) {
                    $adddomain = $domains->addSlave($domainName, $domainPrimary, 'SLAVE');
                    if ($adddomain > 0) {
                        $logs->addLogEntry($adddomain, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'ADD', $domainName, $this->f3->get('SESSION.masteraccountid'));
                        header('Content-type: application/json');
                        echo json_encode(array(
                            'newID' => $adddomain
                        ));
                    } else {
                        $this->returnError("Something has gone wrong");
                        return;
                    }
                } else {
                    $this->returnError($error);
                    return;
                }
                $error = "";
                
            }
            
        } elseif ($adminLevel == '1') {
            // Domain Admin
            $usercurrentaccounts = $domainData->countUserDomains($userid);
            if ($usercurrentaccounts == $userMaxAccounts) {
                $this->returnError('You already have used the maximum amount of domains on your account.');
                return;
            } else {
                if (empty($domainName)) {
                    $error = "Domain Name Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                if (empty($postDomainEmail)) {
                    $error = "Domain Email Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                
                if (empty($domainPrimary)) {
                    $error = "Domain Primary Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                
                if (empty($domainAdmin)) {
                    $error = "Admin Account Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                
                //Check if domain is already in the database
                $domains->getByDomain($domainName);
                if (!$domains->dry()) {
                    $error = "Domain Already Exists";
                    $this->returnError($error);
                    return;
                }
                // Check if admin account exists
                $users->getById($domainAdmin);
                if ($users->dry()) {
                    $error = "Admin Account Does Not Exist";
                    $this->returnError($error);
                    return;
                }
                
                // Check domain is not IDN, if so convert to puny, if not return original domain
                $domainName = $validate->isAsciiDomain($domainName);
                if ($validate->isValidDomain($domainName, $this->db) == false) {
                    $error = "Sorry, " . $domainName . " is not a Valid Domain Name";
                    $this->returnError($error);
                    return;
                }
                
                // Check Primary is not IDN, if so convert to puny, if not return original domain
                $domainPrimary = $validate->isAsciiDomain($domainPrimary);
                if ($validate->isValidDomain($domainPrimary, $this->db) === false) {
                    $error = "Primary is not a Valid Domain Name";
                    $this->returnError($error);
                    return;
                }
                
                // Check email is not IDN, if so convert to puny, if not return original email
                $domainemail = $validate->isAsciiEmail($postDomainEmail);
                if ($validate->isValidEmail($domainemail) === false) {
                    $error = $domainemail . " is not a Valid Email Address";
                    $this->returnError($error);
                    return;
                }
                
                if (!isset($error)) {
                    $soaData   = Array();
                    $soaData[] = $domainPrimary;
                    $soaData[] = $soa->mailToSOA($domainemail);
                    $soaData[] = date("Ymd") . "00";
                    $soaData[] = $domainRefresh;
                    $soaData[] = $domainRetry;
                    $soaData[] = $domainExpire;
                    $soaData[] = $domainTTL;
                    
                    $soaContent = implode(" ", $soaData);
                    $adddomain  = $domains->add($domainName, 'MASTER');
                    if ($adddomain > 0) {
                        $addsoa     = $records->addSOA($adddomain, $soaContent, $domainName, 'SOA', $domainTTL, '0');
                        $adddomhash = $domainData->addDomainData($adddomain, $domainName, $domainAdmin);
                        if ($addsoa >= 0 && $adddomhash === true) {
                            $logs->addLogEntry($adddomain, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'ADD', $domainName, $this->f3->get('SESSION.masteraccountid'));
                            echo $adddomain;
                            return;
                        } else {
                            $error = "Something Went Wrong";
                            $this->returnError($error);
                            return;
                        }
                    } else {
                        $error = "Something has gone wrong";
                        $this->returnError($error);
                        return;
                    }
                } else {
                    $this->returnError($error);
                    return;
                }
                $error = "";
            }
        } else {
            $this->returnError('Your account cannot do that');
            return;
        }
    }
    
    public function ajaxaDeleteDomain($f3)
    {
        $template   = new Template;
        $domains    = new Domains($this->db);
        $users      = new Users($this->db);
        $soa        = new SOA($this->db);
        $validate   = new Validate($this->db);
        $domainData = new DomainData($this->db);
        $logs       = new BigBrother($this->db);
        $adminLevel = $this->f3->get('SESSION.adminlevel');
        $domainID   = $f3->get('POST.domainid');
        $domainName = $f3->get('POST.name');
        if ($adminLevel == '2') {
            if (empty($domainID)) {
                $error = "A problem occured, please refresh the page.";
            }
            
            if (!$error) {
                $deletedomain     = $domains->delete($domainID);
                $deletedomaindata = $domainData->deleteByDomainID($domainID);
                $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'DELETE', $domainName, $this->f3->get('SESSION.masteraccountid'));
                http_response_code(200);
                return;
            } else {
                $this->returnError($error);
                return;
            }
        }
        if ($adminLevel == '1') {
            if ($domainData->checkIsOwner($domainID, $this->f3->get('SESSION.masteraccountid')) == true) {
                if (empty($domainID)) {
                    $error = "A problem occured, please refresh the page.";
                }
                
                if (!$error) {
                    $deletedomain     = $domains->delete($domainID);
                    $deletedomaindata = $domainData->deleteByDomainID($domainID);
                    $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'DELETE', $domainName, $this->f3->get('SESSION.masteraccountid'));
                    http_response_code(200);
                    return;
                } else {
                    $this->returnError($error);
                    return;
                }
            }
        } else {
            $this->returnError('Your account cannot do that');
        }
    }
    
    public function ajaxSOAUpdate($f3)
    {
        $template   = new Template;
        $domains    = new Domains($this->db);
        $domainData = new DomainData($this->db);
        $records    = new Records($this->db);
        $users      = new Users($this->db);
        $soa        = new SOA($this->db);
        $logs       = new BigBrother($this->db);
        $validate   = new Validate($this->db);
        $adminLevel = $this->f3->get('SESSION.adminlevel');
        $domainID   = $f3->get('POST.domainid');
        $domainName = $f3->get('POST.domainname');
        $soaprimary = $f3->get('POST.soaPrimary');
        $soamail    = $f3->get('POST.soaMail');
        $soaretry   = $f3->get('POST.soaRetry');
        $soaexpire  = $f3->get('POST.soaExpire');
        $soattl     = $f3->get('POST.soaTtl');
        $soaserial  = $f3->get('POST.soaSerial');
        $soarefresh = $f3->get('POST.soaRefresh');
        $userID     = $f3->get('SESSION.userid');
        if ($adminLevel == "2") {
            if ($validate->isValidDomainID($domainID, $this->db) == false) {
                $error = "Not a Valid Domain ID";
                $this->returnError($error);
                return;
            }
            
            // Check domain is not IDN, if so convert to puny, if not return original domain
            $soaprimary = $validate->isAsciiDomain($soaprimary);
            if ($validate->isValidDomain($soaprimary, $this->db) === false) {
                $error = "Primary is not a Valid Domain Name";
                $this->returnError($error);
                return;
            }
            
            // Check email is not IDN, if so convert to puny, if not return original email
            $soaemail = $validate->isAsciiEmail($soamail);
            if ($validate->isValidEmail($soaemail) === false) {
                $error = "Not a Valid Email Address";
                $this->returnError($error);
                return;
            }
            
            if ($validate->isValidNumberG0($soaretry) === false) {
                $error = "Retry Value must be a number greater than 0";
                $this->returnError($error);
                return;
            }
            
            if ($validate->isValidNumberG0($soaexpire) === false) {
                $error = "Expire Value must be a number greater than 0";
                $this->returnError($error);
                return;
            }
            
            if ($validate->isValidNumberG0($soattl) === false) {
                $error = "TTL Value must be a number greater than 0";
                $this->returnError($error);
                return;
            }
            
            if ($validate->isValidNumberG0($soarefresh) === false) {
                $error = "Refresh Value must be a number greater than 0";
                $this->returnError($error);
                return;
            }
            $masterAccountID = $users->getMasterAccount($userID);
            if ($masterAccountID == "none") {
                $error = "There seems to be a problem with your account, try again?";
                $this->returnError($error);
                return;
            }
            $updatedrecord = $records->updateSOA($domainID, $soaprimary, $soaemail, $soaserial, $soarefresh, $soaretry, $soaexpire, $soattl);
            if ($updatedrecord > 0) {
                $updatedserial = $records->updateSerial($domainID);
                if ($updatedserial > 0) {
                    $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'UPDATE', 'SOA for ' . $domainName, $masterAccountID);
                    header('Content-Type: application/json');
                    echo json_encode(array(
                        "id" => $updatedrecord,
                        "newserial" => $updatedserial
                    ));
                } else {
                    $error = "Record not Updated 1";
                    $this->returnError($error);
                    return;
                }
            } else {
                $error = "Record not Updated";
                $this->returnError($error);
                return;
            }
        }
        if ($adminLevel == "1" || $adminLevel == "0") {
            if ($domainData->checkIsOwner($domainID, $this->f3->get('SESSION.masteraccountid')) == true) {
                if ($validate->isValidDomainID($domainID, $this->db) == false) {
                    $error = "Not a Valid Domain ID";
                    $this->returnError($error);
                    return;
                }
                
                // Check domain is not IDN, if so convert to puny, if not return original domain
                $soaprimary = $validate->isAsciiDomain($soaprimary);
                if ($validate->isValidDomain($soaprimary, $this->db) === false) {
                    $error = "Primary is not a Valid Domain Name";
                    $this->returnError($error);
                    return;
                }
                
                // Check email is not IDN, if so convert to puny, if not return original email
                $soaemail = $validate->isAsciiEmail($soamail);
                if ($validate->isValidEmail($soaemail) === false) {
                    $error = "Not a Valid Email Address";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumberG0($soaretry) === false) {
                    $error = "Retry Value must be a number greater than 0";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumberG0($soaexpire) === false) {
                    $error = "Expire Value must be a number greater than 0";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumberG0($soattl) === false) {
                    $error = "TTL Value must be a number greater than 0";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumberG0($soarefresh) === false) {
                    $error = "Refresh Value must be a number greater than 0";
                    $this->returnError($error);
                    return;
                }
                $masterAccountID = $users->getMasterAccount($userID);
                if ($masterAccountID == "none") {
                    $error = "There seems to be a problem with your account, try again?";
                    $this->returnError($error);
                    return;
                }
                $updatedrecord = $records->updateSOA($domainID, $soaprimary, $soaemail, $soaserial, $soarefresh, $soaretry, $soaexpire, $soattl);
                if ($updatedrecord > 0) {
                    $updatedserial = $records->updateSerial($domainID);
                    if ($updatedserial > 0) {
                        $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'UPDATE', 'SOA for ' . $domainName, $masterAccountID);
                        http_response_code(200);
                        echo json_encode(array(
                            "id" => $updatedrecord,
                            "newserial" => $updatedserial
                        ));
                    } else {
                        $error = "Record not Updated 1";
                        $this->returnError($error);
                        return;
                    }
                } else {
                    $error = "Record not Updated";
                    $this->returnError($error);
                    return;
                }
            }
        }
    }
    
    public function ajaxUpdateRecord($f3)
    {
        $template   = new Template;
        $domains    = new Domains($this->db);
        $domainData = new DomainData($this->db);
        $records    = new Records($this->db);
        $users      = new Users($this->db);
        $logs       = new BigBrother($this->db);
        $soa        = new SOA($this->db);
        $validate   = new Validate($this->db);
        $adminLevel = $this->f3->get('SESSION.adminlevel');
        $userID     = $f3->get('SESSION.userid');
        $records->copyfrom('POST', function($val)
        {
            return array_intersect_key($val, array_flip(array(
                'name',
                'value',
                'pk'
            )));
        });
        $pk    = $f3->get('POST.pk');
        $name  = $f3->get('POST.name');
        $value = $f3->get('POST.value');
        if ($adminLevel == '2') {
            $records->load(array(
                'id=?',
                $pk
            ));
            if ($records->dry()) {
                // Error Handling
                $this->returnError("There's been an internal problem");
                return;
            }
            $domainID        = $records->domain_id;
            $domainInfo      = $domains->getById($domainID);
            $domainName      = $domainInfo->name;
            $masterAccountID = $users->getMasterAccount($userID);
            if ($masterAccountID == "none") {
                $error = "There seems to be a problem with your account, try again?";
                $this->returnError($error);
                return;
            }
            $error = "";
            if ($f3->GET('POST.pk'))
                $records->load(array(
                    'id=?',
                    $f3->GET('POST.pk')
                ));
            if ($f3->exists('POST.name')) {
                if ($f3->get('POST.name') == "name") {
                    $records->set('name', $f3->get('POST.value'));
                }
                
                if ($f3->get('POST.name') == "content") {
                    if ($validate->isValidIP($f3->get('POST.value')) !== false) {
                        if ($f3->get('POST.recordtype') == "A" || $f3->get('POST.recordtype') == "AAAA") {
                            $records->set('content', $f3->get('POST.value'));
                        } else {
                            $errors = $f3->get('POST.recordtype') . " Records content must be a valid IP Address";
                        }
                        if ($f3->get('POST.recordtype') == "MX") {
                            $errors = "MX content must not be an IP Address";
                        }
                    } else {
                        $records->set('content', $f3->get('POST.value'));
                    }
                }
                
                if ($f3->get('POST.name') == "priority") {
                    if ((int) $f3->get('POST.value') == $f3->get('POST.value')) {
                        $records->set('prio', $f3->get('POST.value'));
                    } else {
                        $errors = "Not a valid number";
                    }
                }
                
                if ($f3->get('POST.name') == "ttl") {
                    if ((int) $f3->get('POST.value') == $f3->get('POST.value') && (int) $f3->get('POST.value') > 0) {
                        $records->set('ttl', $f3->get('POST.value'));
                    } else {
                        if ((int) $f3->get('POST.value') > 0) {
                            $errors = "Not a valid number";
                        } else {
                            $errors = "TTL Must be > 0";
                        }
                    }
                }
                
                if (!$errors) {
                    $records->save();
                    $logs->addLogEntry($domainID, $domainName, $f3->get('SESSION.userid'), $f3->get('SESSION.email'), 'UPDATE', $f3->get('POST.name') . ' ' . $f3->get('POST.value'), $masterID);
                    http_response_code(200);
                    header('Content-Type: application/json');
                    echo json_encode(array(
                        "status" => "ok",
                        "msg" => "Your record has been updated"
                    ));
                } else {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(array(
                        "status" => "error",
                        "msg" => $errors
                    ));
                }
            } else {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array(
                    "status" => "error",
                    "msg" => "Your record wasn\'t updated"
                ));
            }
        }
        if ($adminLevel == '1' || $adminLevel == '0') {
            $records->load(array(
                'id=?',
                $pk
            ));
            if ($records->dry()) {
                // Error Handling
                $this->returnError("There's been an internal problem");
                return;
            }
            $domainID   = $records->domain_id;
            $domainInfo = $domains->getById($domainID);
            $domainName = $domainInfo->name;
            $error      = "";
            if ($domainData->checkIsOwner($domainID, $this->f3->get('SESSION.masteraccountid')) == true) {
                if ($f3->GET('POST.pk'))
                    $records->load(array(
                        'id=?',
                        $f3->GET('POST.pk')
                    ));
                if ($f3->exists('POST.name')) {
                    if ($f3->get('POST.name') == "recordtype") {
                        $records->set('type', $f3->get('POST.value'));
                    }
                    
                    if ($f3->get('POST.name') == "name") {
                        $records->set('name', $f3->get('POST.value'));
                    }
                    
                    if ($f3->get('POST.name') == "content") {
                        if ($validate->isValidIP($f3->get('POST.value')) !== false) {
                            if ($f3->get('POST.recordtype') == "A" || $f3->get('POST.recordtype') == "AAAA") {
                                $records->set('content', $f3->get('POST.value'));
                            } else {
                                $errors = $f3->get('POST.recordtype') . " Records content must be a valid IP Address";
                            }
                            if ($f3->get('POST.recordtype') == "MX") {
                                $errors = "MX content must not be an IP Address";
                            }
                        }
                    }
                    
                    if ($f3->get('POST.name') == "priority") {
                        if ((int) $f3->get('POST.value') == $f3->get('POST.value')) {
                            $records->set('prio', $f3->get('POST.value'));
                        } else {
                            $errors = "Not a valid number";
                        }
                    }
                    
                    if ($f3->get('POST.name') == "ttl") {
                        if ((int) $f3->get('POST.value') == $f3->get('POST.value') && (int) $f3->get('POST.value') > 0) {
                            $records->set('ttl', $f3->get('POST.value'));
                        } else {
                            if ((int) $f3->get('POST.value') > 0) {
                                $errors = "Not a valid number";
                            } else {
                                $errors = "TTL Must be > 0";
                            }
                        }
                    }
                    
                    if (!$errors) {
                        $records->save();
                        $logs->addLogEntry($domainID, $domainName, $f3->get('SESSION.userid'), $f3->get('SESSION.email'), 'UPDATE', $f3->get('POST.name') . ' ' . $f3->get('POST.value'), $masterAccountID);
                        http_response_code(200);
                        echo "Record Updated";
                    } else {
                        http_response_code(400);
                        echo $errors;
                    }
                } else {
                    http_response_code(400);
                    echo "Record Not Updated";
                }
            }
            
        }
    }
    
    public function ajaxAddRecord($f3)
    {
        $template       = new Template;
        $domains        = new Domains($this->db);
        $domainData     = new DomainData($this->db);
        $records        = new Records($this->db);
        $users          = new Users($this->db);
        $soa            = new SOA($this->db);
        $logs           = new BigBrother($this->db);
        $validate       = new Validate($this->db);
        $adminLevel     = $this->f3->get('SESSION.adminlevel');
        $recordtype     = $f3->get('POST.type');
        $recordcontent  = $f3->get('POST.content');
        $recordpriority = $f3->get('POST.prio');
        $recordttl      = $f3->get('POST.ttl');
        $domainID       = $f3->get('POST.domain');
        $domainName     = $f3->get('POST.name');
        if ($adminLevel == '2') {
            if (empty($recordtype)) {
                $error = "Record Type Cannot Be Empty";
                $this->returnError($error);
                return;
            }
            
            if (empty($recordcontent)) {
                $error = "Content Cannot Be Empty";
                $this->returnError($error);
                return;
            }
            
            if ((int) $recordpriority < 0) {
                $error = "Priority Must Be A Number 0 or higher";
                $this->returnError($error);
                return;
            }
            
            if ((int) $recordttl < 0) {
                $error = "TTL Must Be A Number 0 or higher";
                $this->returnError($error);
                return;
            }
            
            if ($recordtype == "A" || $recordcontent == "AAAA") {
                if ($validate->isValidIP($f3->get('POST.content')) == false) {
                    $error = $recordtype . " Records <b>content</b> must be an IP Address";
                    $this->returnError($error);
                    return;
                }
            }
            
            if ($recordtype == "MX") {
                if ($validate->isValidIP($f3->get('POST.content')) !== false) {
                    $error = "MX Records <b>content</b> must not be an IP Address";
                    $this->returnError($error);
                    return;
                }
            }
            
            if ($domainName == $recordcontent) {
                $error = "You cannot point the domain back to itself";
                $this->returnError($error);
                return;
            }
            
            if (!isset($error)) {
                $addrecord = $records->addNewHost($domainID, $domainName, $recordtype, $recordcontent, $recordpriority, $recordttl);
                if ($addrecord >= 0) {
                    $updatedserial = $records->updateSerial($domainID);
                    $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'ADD', $recordtype . ' ' . $recordcontent, $this->f3->get('SESSION.masteraccountid'));
                    http_response_code(200);
                    echo json_encode(array(
                        "newid" => $addrecord,
                        "newserial" => $updatedserial
                    ));
                } else {
                    $error = "Something Went Wrong";
                    $this->returnError($error);
                    return;
                }
            } else {
                $this->returnError($error);
                return;
            }
            $error = "";
        }
        if ($adminLevel == '1' || $adminLevel == '0') {
            if ($domainData->checkIsOwner($domainID, $this->f3->get('SESSION.masteraccountid')) == true) {
                if (empty($recordtype)) {
                    $error = "Record Type Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                
                if (empty($recordcontent)) {
                    $error = "Content Cannot Be Empty";
                    $this->returnError($error);
                    return;
                }
                
                if ((int) $recordpriority < 0) {
                    $error = "Priority Must Be A Number 0 or higher";
                    $this->returnError($error);
                    return;
                }
                
                if ((int) $recordttl < 0) {
                    $error = "TTL Must Be A Number 0 or higher";
                    $this->returnError($error);
                    return;
                }
                
                if ($recordtype == "A" || $recordcontent == "AAAA") {
                    if ($validate->isValidIP($f3->get('POST.content')) == false) {
                        $error = $recordtype . " Records <b>content</b> must be an IP Address";
                        $this->returnError($error);
                        return;
                    }
                }
                
                if ($recordtype == "MX") {
                    if ($validate->isValidIP($f3->get('POST.content')) !== false) {
                        $error = "MX Records <b>content</b> must not be an IP Address";
                        $this->returnError($error);
                        return;
                    }
                }
                
                if ($domainName == $recordcontent) {
                    $error = "You cannot point the domain back to itself";
                    $this->returnError($error);
                    return;
                }
                
                if (!isset($error)) {
                    $addrecord = $records->addNewHost($domainID, $domainName, $recordtype, $recordcontent, $recordpriority, $recordttl);
                    if ($addrecord >= 0) {
                        $updatedserial = $records->updateSerial($domainID);
                        $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'ADD', $recordtype . ' ' . $recordcontent, $this->f3->get('SESSION.masteraccountid'));
                        http_response_code(200);
                        echo json_encode(array(
                            "newid" => $addrecord,
                            "newserial" => $updatedserial
                        ));
                    } else {
                        $error = "Something Went Wrong";
                        $this->returnError($error);
                        return;
                    }
                } else {
                    $this->returnError($error);
                    return;
                }
                $error = "";
            }
        }
    }
    
    public function ajaxDeleteRecord($f3)
    {
        $template      = new Template;
        $domains       = new Domains($this->db);
        $domainData    = new DomainData($this->db);
        $records       = new Records($this->db);
        $users         = new Users($this->db);
        $soa           = new SOA($this->db);
        $logs          = new BigBrother($this->db);
        $validate      = new Validate($this->db);
        $adminLevel    = $this->f3->get('SESSION.adminlevel');
        $recordid      = $f3->get('POST.id');
        $recordtype    = $f3->get('POST.type');
        $recordname    = $f3->get('POST.name');
        $recordcontent = $f3->get('POST.content');
        $domainID      = $f3->get('POST.domainid');
        $domainName    = $f3->get('POST.domain');
        if ($adminLevel == '2') {
            if (empty($recordtype)) {
                $error = "Record Type Cannot Be Empty";
            }
            
            if (empty($recordcontent)) {
                $error = "Content Cannot Be Empty";
            }
            
            if (!$error) {
                $deleterecord = $records->deleteHost($recordid, $recordtype, $recordname, $recordcontent, $domainID);
                $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'DELETE', $recordtype . ' ' . $recordname . ' ' . $recordcontent, $this->f3->get('SESSION.masteraccountid'));
                http_response_code(200);
                return;
            } else {
                $this->returnError($error);
                return;
            }
        }
        if ($adminLevel == '1' || $adminLevel == '0') {
            if ($domainData->checkIsOwner($domainID, $this->f3->get('SESSION.masteraccountid')) == true) {
                if (empty($recordtype)) {
                    $error = "Record Type Cannot Be Empty";
                }
                
                if (empty($recordcontent)) {
                    $error = "Content Cannot Be Empty";
                }
                
                if (!$error) {
                    $deleterecord = $records->deleteHost($recordid, $recordtype, $recordname, $recordcontent, $domainID);
                    $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'DELETE', $recordtype . ' ' . $recordname . ' ' . $recordcontent, $this->f3->get('SESSION.masteraccountid'));
                    http_response_code(200);
                    return;
                } else {
                    $this->returnError($error);
                    return;
                }
            }
        } else {
            $this->returnError('Your account cannot do that');
        }
    }
    
    public function ajaxUserUpdate($f3)
    {
        $template          = new Template;
        $domains           = new Domains($this->db);
        $records           = new Records($this->db);
        $users             = new Users($this->db);
        $soa               = new SOA($this->db);
        $validate          = new Validate($this->db);
        $postedData        = json_decode($f3->get('BODY'), true);
        $adminLevel        = $this->f3->get('SESSION.adminlevel');
        $userMaxAccounts   = $this->f3->get('SESSION.maxdomains');
        $userid            = $postedData['userID'];
        $useremail         = $postedData['userEmail'];
        $username          = $postedData['userFullName'];
        $usermasteraccount = $postedData['masterAccount'];
        $maxdomains        = $postedData['userMaxDomains'];
        $userrole          = $postedData['userLevel'];
        $userenabled       = $postedData['userEnabled'];
        $userdisabled      = $postedData['userDisabled'];
        $adminLevel        = $this->f3->get('SESSION.adminlevel');
        $masteraccountid   = $this->f3->get('SESSION.masteraccountid');
        if ($adminLevel == '2') {
            $users->getById($userid);
            if ($users->dry()) {
                $error = "Admin Account Does Not Exist";
                $this->returnError($error);
                return;
            }
            
            if ($validate->isValidNumber($userrole) === false) {
                $error = "User Role value is incorrect";
                $this->returnError($error);
                return;
            }
            
            if ($validate->isValidNumber($maxdomains) === false) {
                $error = "Max Accounts value is incorrect";
                $this->returnError($error);
                return;
            }
            
            if (isset($userenabled)) {
                $useractive = "1";
            }
            
            if (isset($userdisabled)) {
                $useractive = "0";
            }
            
            $updateduser = $users->updateUser($userid, $useremail, $userrole, $username, $maxdomains, $useractive, $usermasteraccount);
            if ($updateduser !== false) {
                http_response_code(200);
                echo json_encode(array(
                    "Message" => "User Updated"
                ));
            } else {
                $error = "Record not Updated";
                $this->returnError($error);
                return;
            }
        }
        
        if ($adminLevel == '1') {
            if ($users->checkIsMaster($userid, $masteraccountid) == true) {
                $users->getById($userid);
                if ($users->dry()) {
                    $error = "Admin Account Does Not Exist";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($userrole) === false) {
                    $error = "User Role value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($maxdomains) === false) {
                    $error = "Max Accounts value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($userenabled) === false) {
                    $error = "User Enabled value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                $updateduser = $users->updateUser($userid, $useremail, $userrole, $username, $maxdomains, $userenabled);
                if ($updateduser !== false) {
                    http_response_code(200);
                    echo json_encode(array(
                        "newemail" => $updateduser
                    ));
                } else {
                    $error = "Record not Updated";
                    $this->returnError($error);
                    return;
                }
            } else {
                $this->error("Record Not Updated");
                return;
            }
        } else {
            // $this->returnError('Your account cannot do that');
        }
    }
    
    public function ajaxUserPassword($f3)
    {
        $template        = new Template;
        $users           = new Users($this->db);
        $soa             = new SOA($this->db);
        $validate        = new Validate($this->db);
        $postedData      = json_decode($f3->get('BODY'), true);
        $adminLevel      = $this->f3->get('SESSION.adminlevel');
        $userMaxAccounts = $this->f3->get('SESSION.maxdomains');
        $userid          = $postedData['userID'];
        $useremail       = $postedData['passwordOne'];
        $username        = $postedData['passwordTwo'];
        $adminLevel      = $this->f3->get('SESSION.adminlevel');
        $masteraccountid = $this->f3->get('SESSION.masteraccountid');
        if ($adminLevel == '2') {
            $users->getById($userid);
            if ($users->dry()) {
                $error = "User Account Does Not Exist";
                $this->returnError($error);
                return;
            }
            if ($passwordOne == $passwordTwo) {
                $updateduser = $users->updateUserPassword($userid, $passwordOne);
                if ($updateduser !== false) {
                    http_response_code(200);
                    echo json_encode(array(
                        "Message" => "User Password Changed"
                    ));
                } else {
                    $error = "User Password Not Changed";
                    $this->returnError($error);
                    return;
                }
            } else {
                $error = "Passwords Don't Match";
                $this->returnError($error);
                return;
            }
            
        }
        
        if ($adminLevel == '1') {
            if ($users->checkIsMaster($userid, $masteraccountid) == true) {
                $users->getById($userid);
                if ($users->dry()) {
                    $error = "Admin Account Does Not Exist";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($userrole) === false) {
                    $error = "User Role value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($maxdomains) === false) {
                    $error = "Max Accounts value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($userenabled) === false) {
                    $error = "User Enabled value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                $updateduser = $users->updateUser($userid, $useremail, $userrole, $username, $maxdomains, $userenabled);
                if ($updateduser !== false) {
                    http_response_code(200);
                    echo json_encode(array(
                        "newemail" => $updateduser
                    ));
                } else {
                    $error = "Record not Updated";
                    $this->returnError($error);
                    return;
                }
            } else {
                $this->error("Record Not Updated");
                return;
            }
        } else {
            // $this->returnError('Your account cannot do that');
        }
    }
    
    public function ajaxResetUserPassword($f3)
    {
        $template        = new Template;
        $users           = new Users($this->db);
        $validate        = new Validate($this->db);
        $postedData      = json_decode($f3->get('BODY'), true);
        $adminLevel      = $this->f3->get('SESSION.adminlevel');
        $userMaxAccounts = $this->f3->get('SESSION.maxdomains');
        $userEmail       = $postedData['userEmail'];
        $adminLevel      = $this->f3->get('SESSION.adminlevel');
        $masteraccountid = $this->f3->get('SESSION.masteraccountid');
        if ($adminLevel == '2') {
            if ($users->checkEmailExists($userEmail) == 1) {
                // Users Email exists in system, send out reset email.
                $userResetToken = $users->createResetToken();
                $users->updateUserResetToken($userEmail, $userResetToken, date("Y-m-d H:i:s", strtotime('+1 hour')));
                $mail = new PHPMailer(true);
                try {
                    $mail->SMTPDebug = $this->f3->get('SMTPDEBUG');
                    $mail->isSMTP();
                    $mail->Host       = $this->f3->get('SMTPHOST');
                    $mail->SMTPAuth   = $this->f3->get('SMTPAUTH');
                    $mail->Username   = $this->f3->get('SMTPUSERNAME');
                    $mail->Password   = $this->f3->get('SMTPPASSWORD');
                    $mail->SMTPSecure = $this->f3->get('SMTPSECURE');
                    $mail->Port       = $this->f3->get('SMTPPORT');
                    $mail->setFrom($this->f3->get('SMTPPWRESETFROMEMAIL'), $this->f3->get('SMTPPWRESETFROMNAME'));
                    $mail->addAddress($userEmail, 'User');
                    $mail->isHTML(true);
                    $mail->Subject     = $this->f3->get('SITENAME') . ' Password Reset';
                    $passwordResetLink = $this->f3->get('SITEURL') . "reset-password/" . $userResetToken;
                    $emailMessage      = file_get_contents('../framework/views/emails/reset-password.html');
                    $emailMessage      = str_replace('@@SITEURL@@', $this->f3->get('SITEURL'), $emailMessage);
                    $emailMessage      = str_replace('@@SITENAME@@', $this->f3->get('SITENAME'), $emailMessage);
                    $emailMessage      = str_replace('@@PASSWORDRESETLINK@@', $passwordResetLink, $emailMessage);
                    $emailMessage      = str_replace('@@RESETTOKEN@@', $userResetToken, $emailMessage);
                    $mail->Body        = $emailMessage;
                    $mail->send();
                    $resetMessage = "The email has been sent to the user";
                }
                catch (Exception $e) {
                    $error = "There appears to have been some kind of problem sending the email, the error message is: <strong>{$mail->ErrorInfo}</strong>";
                    $this->returnError($error);
                    return;
                }
            } else {
                $resetMessage = "The email has been sent to the user";
            }
            http_response_code(200);
            echo json_encode(array(
                "Message" => $resetMessage
            ));
            
        }
        
        if ($adminLevel == '1') {
            if ($users->checkIsMaster($userid, $masteraccountid) == true) {
                $users->getById($userid);
                if ($users->dry()) {
                    $error = "Admin Account Does Not Exist";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($userrole) === false) {
                    $error = "User Role value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($maxdomains) === false) {
                    $error = "Max Accounts value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                if ($validate->isValidNumber($userenabled) === false) {
                    $error = "User Enabled value is incorrect";
                    $this->returnError($error);
                    return;
                }
                
                $updateduser = $users->updateUser($userid, $useremail, $userrole, $username, $maxdomains, $userenabled);
                if ($updateduser !== false) {
                    http_response_code(200);
                    echo json_encode(array(
                        "newemail" => $updateduser
                    ));
                } else {
                    $error = "Record not Updated";
                    $this->returnError($error);
                    return;
                }
            } else {
                $this->error("Record Not Updated");
                return;
            }
        } else {
            // $this->returnError('Your account cannot do that');
        }
    }
    
    public function ajaxUserAdd($f3)
    {
        $template        = new Template;
        $domains         = new Domains($this->db);
        $records         = new Records($this->db);
        $users           = new Users($this->db);
        $soa             = new SOA($this->db);
        $validate        = new Validate($this->db);
		$postedData      = json_decode($f3->get('BODY'), true);
        $adminLevel      = $this->f3->get('SESSION.adminlevel');
        $useremail       = $postedData["userEmail"];
		$userFullName    = $postedData["addUserFullName"];
        $userLevel       = $postedData["addUserLevel"];
		$userMaxDoms     = $postedData["addUserMaxDoms"];
		$userPassword    = $postedData["addUserPassword"];
		$userMaster      = $postedData["addUserMaster"];
        $masteraccountid = $this->f3->get('SESSION.masteraccountid');
        $userenabled     = '1';
        if ($adminLevel == '2') {
            if (empty($useremail)) {
                $error = "The Users Email Cannot Be Empty";
                $this->returnError($error);
                return;
            } else {
                if ($validate->isValidEmail($useremail) === false) {
                    $error = "The Users Email Appears To Be Incorrect";
                    $this->returnError($error);
                    return;
                }
            }
            $users->getByEmail($useremail);
            if (!$users->dry()) {
                $error = "The Users Email Account Is Already In The Database";
                $this->returnError($error);
                return;
            }
            
            if (empty($userFullName)) {
                $error = "The Users Name Cannot Be Empty";
                $this->returnError($error);
                return;
            }
            
            if (empty($userLevel)) {
                if ($validate->isValidNumber($userLevel) === false) {
                    $error = "Max Accounts value is incorrect";
                    $this->returnError($error);
                    return;
                }
            }
            
            if (empty($userMaxDoms) && !strlen($userMaxDoms)) {
                $error = "Max Domains Must Not Be Empty";
                $this->returnError($error);
                return;
            } else {
                if ($validate->isValidNumber($userMaxDoms) === false) {
                    $error = "Max Domains Must Be A Number";
                    $this->returnError($error);
                    return;
                }
            }
            
            if (empty($userPassword)) {
                $error = "Password Field Cannot Be Empty";
                $this->returnError($error);
                return;
            }
            
            if (strlen($userPassword) < 5) {
                $error = "Password Must Be At Least 5 Characters";
                $this->returnError($error);
                return;
            }
            
            if ($adminLevel == 2) {
                $masteraccountid = $f3->get('SITEMASTERUSERID');
            }

            $adduser    = $users->add($useremail, $userFullName, $userLevel, $userMaxDoms, $userPassword, $userMaster);
            if ($adduser >= 0) {
                http_response_code(200);
                echo json_encode(array(
                    "userid" => $adduser
                ));
            } else {
                $error = "User Not Added";
                $this->returnError($error);
                return;
            }
        }
        
        if ($adminLevel == '1') {
            if (empty($useremail)) {
                $error = "The Users Email Cannot Be Empty";
                $this->returnError($error);
                return;
            } else {
                if ($validate->isValidEmail($useremail) === false) {
                    $error = "The Users Email Appears To Be Incorrect";
                    $this->returnError($error);
                    return;
                }
            }
            $users->getByEmail($useremail);
            if (!$users->dry()) {
                $error = "The Users Email Account Is Already In The Database";
                $this->returnError($error);
                return;
            }
            
            if (empty($username)) {
                $error = "The Users Name Cannot Be Empty";
                $this->returnError($error);
                return;
            }
            
            if (empty($adminLevel)) {

                if ($validate->isValidNumber($adminLevel) === false) {
                    $error = "User Level value is incorrect";
                    $this->returnError($error);
                    return;
                }
            }
            
            if ($adminLevel == '2') {
                $this->returnError("You don't have permission to do that");
            }
            
            if (empty($maxdomains) && !strlen($maxdomains)) {
                $error = "Max Domains Must Not Be Empty";
                $this->returnError($error);
                return;
            } else {
                if ($validate->isValidNumber($maxdomains) === false) {
                    $error = "Max Domains Must Be A Number";
                    $this->returnError($error);
                    return;
                }
            }
            
            if (empty($password1)) {
                $error = "First Password Field Cannot Be Empty";
                $this->returnError($error);
                return;
            }
            
            if (empty($password2)) {
                $error = "Second Password Field Cannot Be Empty";
                $this->returnError($error);
                return;
            }
            
            if ($password1 !== $password2) {
                $error = "Passwords Do not Match";
                $this->returnError($error);
                return;
            }
            
            if (strlen($password1) < 9) {
                $error = "Passwords Must Be At Least 9 Characters";
                $this->returnError($error);
                return;
            }
            
            $useremail  = $f3->get('POST.useremail');
            $username   = $f3->get('POST.username');
            $userrole   = $f3->get('POST.role');
            $maxdomains = $f3->get('POST.maxdomains');
            $password1  = $f3->get('POST.userpassword1');
            $password2  = $f3->get('POST.userpassword2');
            $adduser    = $users->add($useremail, $username, $userrole, $maxdomains, $password1, $masteraccountid);
            if ($adduser >= 0) {
                http_response_code(200);
                echo json_encode(array(
                    "userid" => $adduser
                ));
            } else {
                $error = "User Not Added";
                $this->returnError($error);
                return;
            }
        } else {
            //$this->returnError('Your account cannot do that');
        }
    }
    
    public function ajaxUserDelete($f3)
    {
        $template   = new Template;
        $users      = new Users($this->db);
        $validate   = new Validate($this->db);
        $domainData = new DomainData($this->db);
        $logs       = new BigBrother($this->db);
        $adminLevel = $this->f3->get('SESSION.adminlevel');
        $userid     = $f3->get('POST.userid');
        $useremail  = $f3->get('POST.useremail');
        if ($adminLevel == '2') {
            if (empty($userid)) {
                $error = "A problem occured, please refresh the page.";
            }
            
            if (!$error) {
                $user = $users->delete($userid);
                $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'DELETED', $useremail, $this->f3->get('SESSION.masteraccountid'));
                http_response_code(200);
                return;
            } else {
                $this->returnError($error);
                return;
            }
        }
        if ($adminLevel == '1') {
            if ($users->checkIsMaster($userid, $this->f3->get('SESSION.masteraccountid')) == true) {
                if (empty($userid)) {
                    $error = "A problem occured, please refresh the page.";
                }
                
                if (!$error) {
                    $user = $users->delete($userid);
                    $logs->addLogEntry($domainID, $domainName, $this->f3->get('SESSION.userid'), $this->f3->get('SESSION.email'), 'DELETED', $useremail, $this->f3->get('SESSION.masteraccountid'));
                    http_response_code(200);
                    return;
                } else {
                    $this->returnError($error);
                    return;
                }
            }
        } else {
            $this->returnError('Your account cannot do that');
        }
    }
    
    public function newPassword()
    {
        $validate = new Validate($this->db);
        $newpass  = $validate->genPassword();
        echo json_encode(array(
            "password" => $newpass
        ));
    }
    
    public function returnError($error)
    {
        http_response_code(400);
        echo $error;
    }
}