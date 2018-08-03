<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});
Route::prefix('api')->group(function (){
    Route::get('/businessList','ShopController@businessList')->name('businessList');//商家列表接口
    Route::get('/business','ShopController@business')->name('business');//获取指定商家接口
    Route::get('/sms', 'ShopController@sms')->name('sms');//短信验证
    Route::post('/regist', 'ShopController@regist')->name('regist');//用户注册
    Route::post('/loginCheck', 'ShopController@loginCheck')->name('loginCheck');//登录验证
    Route::get('/addressList', 'ShopController@addressList')->name('addressList');//地址列表
    Route::post('/addAddress', 'ShopController@addAddress')->name('addAddress');//保存新增地址
    Route::get('/address', 'ShopController@address')->name('address');//指定地址
    Route::post('/editAddress', 'ShopController@editAddress')->name('editAddress');//保存修改地址
    Route::post('/addCart', 'ShopController@addCart')->name('addCart');//保存购物车
    Route::get('/cart', 'ShopController@cart')->name('cart');//获取购物车数据
    Route::post('/addOrder', 'ShopController@addOrder')->name('addOrder');//添加订单
    Route::get('/order', 'ShopController@order')->name('order');//获取指定订单
    Route::get('/orderList', 'ShopController@orderList')->name('orderList');//获取订单列表
    Route::post('/changePassword', 'ShopController@changePassword')->name('changePassword');//修改密码
    Route::post('/forgetPassword', 'ShopController@forgetPassword')->name('forgetPassword');//忘记密码
});
