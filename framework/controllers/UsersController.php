<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UsersController extends Controller
{
    public function renderLogin($f3)
    {
        $template = new Template;
        echo $template->render('login.html');
    }
    
    public function renderForgotPassword($f3)
    {
        $template = new Template;
        echo $template->render('forgot-password.html');
    }
    
    public function forgotPassword($f3)
    {
        $template  = new Template;
        $users     = new Users($this->db);
        $userEmail = $f3->get('POST.email');
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
                $resetMessage = "If we find your email address in our system, an email containing the reset instructions will be sent. <strong>Please check your spam folders if it doesn't arrive shortly.</strong>";
            }
            catch (Exception $e) {
                $resetMessage = "There appears to have been some kind of problem sending the email, the error message is: <strong>{$mail->ErrorInfo}</strong>";
            }
        } else {
            $resetMessage = "If we find your email address in our system, an email containing the reset instructions will be sent. <strong>Please check your spam folders if it doesn't arrive shortly.</strong>";
        }
        $this->f3->set('RESETMESSAGE', $resetMessage);
        echo $template->render('forgot-password-sent.html');
    }
    
    public function renderResetPassword($f3)
    {
        $template = new Template;
        if ($this->f3->get('PARAMS.RESETTOKEN') != "") {
            $this->f3->set('RESETTOKEN', $this->f3->get('PARAMS.RESETTOKEN'));
        }
        echo $template->render('reset-password.html');
    }
    
    public function resetPassword($f3)
    {
        $template      = new Template;
        $users         = new Users($this->db);
        $resetToken    = $this->f3->get('POST.token');
        $userPassword  = $this->f3->get('POST.passwordTwo');
        $resetPassword = $users->resetUserPasswordViaToken($resetToken, $userPassword);
        if ($resetPassword === "changed") {
            Flash::instance()->addMessage('Your password has been changed', 'success');
            $f3->reroute('/login');
        } else {
            $this->f3->set('RESETTOKEN', $this->f3->get('POST.token'));
            Flash::instance()->addMessage('There was a problem changing your password, try again.', 'danger');
            echo $template->render('reset-password.html');
        }
    }
    
    public function generateAvatar()
    {
        $usersName = $this->f3->get('PARAMS.USERSNAME');
        header('Content-Type: image/png');
        $avatar = new LasseRafn\InitialAvatarGenerator\InitialAvatar();
        echo $avatar->name($usersName)->height(800)->width(800)->background('#11cdef')->color('#ffffff')->generate()->stream('png', 100);
    }
    
    public function beforeroute()
    {
    }
    
    public function authenticate($f3)
    {
        
        $email    = $this->f3->get('POST.email');
        $password = $this->f3->get('POST.password');
        
        $users     = new Users($this->db);
        $userlevel = new UserLevel($this->db);
        $logins    = new Logins($this->db);
        $users->getByEmail($email);
        
        if ($users->dry()) {
            $this->f3->reroute('/login');
        }
        
        if (password_verify($password, $users->userPassword)) {
            if ($users->userEnabled == "1") {
                $this->f3->set('SESSION.email', $users->userEmail);
                $this->f3->set('SESSION.realname', $users->userName);
                $this->f3->set('SESSION.adminlevel', $users->userAdminLevel);
                $this->f3->set('SESSION.adminleveldesc', $userlevel->getLevelDesc($users->userAdminLevel));
                $this->f3->set('SESSION.maxdomains', $users->userMaxDomains);
                $this->f3->set('SESSION.userid', $users->userID);
                $this->f3->set('SESSION.masteraccountid', $users->userMasterAccount);
                if ($this->f3->get('POST.rememberMe')) {
                    $authTokens         = new AuthTokens($this->db);
                    $generateAuthTokens = $authTokens->generateAuthTokens($users->userID);
                }
                $logins->add($users->userID, $this->f3->get('IP'), $this->f3->get('AGENT'), $users->userMasterAccount);
                $this->f3->reroute('/');
            } else {
                Flash::instance()->addMessage('Your account is disabled. Please contact your Domain Administrator', 'danger');
                $this->f3->reroute('/login');
            }
        } else {
            Flash::instance()->addMessage('Incorrect Email or Password.', 'danger');
            $this->f3->reroute('/login');
        }
    }
    
    public function logout($f3)
    {
        $this->f3->clear('SESSION');
        setcookie('rememberMe', null, -1, '/');
        $this->f3->reroute('/');
    }
}