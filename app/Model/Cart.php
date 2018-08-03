<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    //设置权限
    protected $fillable = ['user_id','goods_id','amount','created_at','updated_at'];
}
