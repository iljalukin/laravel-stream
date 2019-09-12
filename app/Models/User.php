<?php

namespace App\Models;

class User extends \Encore\Admin\Auth\Database\Administrator
{
    public function identities()
    {
        return $this->belongsToMany('App\Models\User', 'users');
    }
}
?>