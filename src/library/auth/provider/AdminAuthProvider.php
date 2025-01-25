<?php

declare(strict_types=1);

namespace mulo\library\auth\provider;

use mulo\library\auth\AuthProvider;

// use mulo\tpmodel\user\User as UserModel;


class AdminAuthProvider extends AuthProvider
{
   
    public $scene = 'admin';
    

}
