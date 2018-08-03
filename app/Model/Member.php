<?php

namespace App\Model;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    use Notifiable;
   protected $fillable = ['username','tel','password'];
}
