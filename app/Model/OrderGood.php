<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderGood extends Model
{
    //设置权限
    protected $fillable = ['order_id','goods_id','amount','goods_name','goods_img','goods_price','created_at','updated_at'];

    public function order()
    {
        return $this->belongsTo(Order::class,'order_id','id');
    }
}
