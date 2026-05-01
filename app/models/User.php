<?php
namespace App\Models;

use Briko\Database\Model;

class User extends Model
{
    protected static string $table      = 'users';
    protected static string $primaryKey = 'id';

    // Relations exemple — à appeler depuis un controller ou service :
    // $posts = User::hasMany('posts', 'user_id', $userId);
    // $role  = User::belongsTo('roles', $user['role_id']);
}
