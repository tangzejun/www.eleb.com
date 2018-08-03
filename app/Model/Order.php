<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //设置权限
    protected $fillable = ['user_id','shop_id','sn','province','city','county','address','tel','name','total','status','created_at','out_trade_no','updated_at'];
}
