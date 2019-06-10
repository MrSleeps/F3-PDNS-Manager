<?php

class SiteAdminController extends Controller
{
    
    public function isSiteAdmin($f3)
    {
        $adminlevel = $this->f3->get('SESSION.adminlevel');
        if ($adminlevel == 2) {
            // User is SiteAdmin
            return true;
        } else {
            $this->f3->reroute('/error/permission');
        }
    }
    
}