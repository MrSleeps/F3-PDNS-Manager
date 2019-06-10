<?php
$f3=require('../framework/lib/base.php');
$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');
if(session_status() == PHP_SESSION_NONE || session_status() == 0){
    //session has not started
    session_start();
}
include_once('assets/vendor/autoload.php');
$f3->config('../config/config.ini');
// Main Dashboard Controller
$f3->route('GET /','DashboardController->renderDashboard');
// Domains
$f3->route('GET /domains','DashboardController->renderViewDomains');
$f3->route('GET /domains/add','DashboardController->renderAddDomain');
$f3->route('GET /domains/edit/@DOMAINID','DashboardController->renderEditDomain');
// Domains AJAX
$f3->route('POST /ajax/domains/add [ajax]','AjaxController->ajaxAddDomain');
$f3->route('POST /ajax/domains/delete [ajax]','AjaxController->ajaxaDeleteDomain');
$f3->route('POST /ajax/records/soaupdate [ajax]','AjaxController->ajaxSOAUpdate');
$f3->route('POST /ajax/records/update [ajax]','AjaxController->ajaxUpdateRecord');
$f3->route('POST /ajax/records/add [ajax]','AjaxController->ajaxaddrecord');
$f3->route('POST /ajax/records/delete [ajax]','AjaxController->ajaxdeleterecord');
// Users
$f3->route('GET /users','DashboardController->renderViewUsers');
$f3->route('GET /users/add','DashboardController->renderAddUser');
$f3->route('GET /users/edit/@USERID','DashboardController->renderEditUser');
// Users AJAX
$f3->route('POST /ajax/users/update [ajax]','AjaxController->ajaxuserupdate');
$f3->route('POST /ajax/users/add [ajax]','AjaxController->ajaxuseradd');
$f3->route('POST /ajax/users/delete [ajax]','AjaxController->ajaxuserdelete');
// Logs
$f3->route('GET /logs','DashboardController->renderLogsDashboard');
$f3->route('GET /logs/logins','DashboardController->renderLogsLogins');
$f3->route('GET /logs/system','DashboardController->renderLogsSystem');


// General AJAX
$f3->route('GET /ajax/password [ajax]','AjaxController->newPassword');
// Handle Logins & Logout
$f3->route('GET /login','UsersController->renderLogin');
$f3->route('POST /login','UsersController->authenticate');
$f3->route('GET /logout','UsersController->logout');
$f3->route('GET /forgot-password','UsersController->renderForgotPassword');
$f3->route('POST /forgot-password','UsersController->forgotPassword');
$f3->route('GET /reset-password/@RESETTOKEN','UsersController->renderResetPassword');
$f3->route('GET /reset-password','UsersController->renderResetPassword');
$f3->route('POST /reset-password','UsersController->resetPassword');
$f3->route('GET /avatar/@USERSNAME', 'UsersController->generateAvatar');
$f3->run();
