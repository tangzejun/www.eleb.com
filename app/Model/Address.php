<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    //设置权限
    protected $fillable = ['user_id','province','city','county','address','tel','name','is_default','created_at','updated_at'];
}
