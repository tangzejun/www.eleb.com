<?php

namespace App\Http\Controllers;

use App\Model\Address;
use App\Model\Cart;
use App\Model\Member;
use App\Model\Menu;
use App\Model\MenuCategory;
use App\Model\Order;
use App\Model\OrderGood;
use App\Model\Shop;
use App\SignatureHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    /**
     * "id": "s10001",
     * "shop_name": "上沙麦当劳",
     * "shop_img": "http://www.homework.com/images/shop-logo.png",
     * "shop_rating": 4.7,评分
     * "brand": true,是否是品牌
     * "on_time": true,是否准时送达
     * "fengniao": true,是否蜂鸟配送
     * "bao": true,是否保标记
     * "piao": true,是否票标记
     * "zhun": true,是否准标记
     * "start_send": 20,起送金额
     * "send_cost": 5,配送费
     * "distance": 637,距离
     * "estimate_time": 30,预计时间
     * "notice": "新店开张，优惠大酬宾！",店公告
     * "discount": "新用户有巨额优惠！"优惠信息
     */
    //获取商家列表
    public function businessList(Request $request)
    {
       $shops = Shop::select('id','shop_name','shop_img','shop_rating','brand','fengniao','bao','piao','zhun','start_send','send_cost','notice','discount')->where('shop_name','like',"%$request->keyword%")->get();
        foreach ($shops as $shop){
            $shop['distance']=mt_rand(500,10000);
            $shop['estimate_time']=mt_rand(20,150);
        }
        //dd($shops);
        return json_encode($shops);
    }

    /**
     * "id": "s10001",
     * "shop_name": "上沙麦当劳",
     * "shop_img": "http://www.homework.com/images/shop-logo.png",
     * "shop_rating": 4.5,
     * "service_code": 4.6,// 服务总评分
     * "foods_code": 4.4,// 食物总评分
     * "high_or_low": true,// 低于还是高于周边商家
     * "h_l_percent": 30,// 低于还是高于周边商家的百分比
     * "brand": true,
     * "on_time": true,
     * "fengniao": true,
     * "bao": true,
     * "piao": true,
     * "zhun": true,
     * "start_send": 20,
     * "send_cost": 5,
     * "distance": 637,
     * "estimate_time": 31,
     * "notice": "新店开张，优惠大酬宾！",
     * "discount": "新用户有巨额优惠！",
     * "evaluate": [{评价
    "user_id": 12344
    "username": "w******k"用户名
    "user_img": "http://www.homework.com/images/slider-pic4.jpeg",
    "time": "2017-2-22",
    "evaluate_code": 1,评分
    "send_time": 30,送达时间
    "evaluate_details": "不怎么好吃"
    }
    ],
     * "commodity": [{//店铺商品
    "description": "大家喜欢吃，才叫真好吃。",分类描述
    "is_selected": true,是否选中
    "name": "热销榜",分类名称
    "type_accumulation": "c1",//类型id
    "goods_list": [{类型下的商品
    "goods_id": 100001,
    "goods_name": "吮指原味鸡",
    "rating": 4.67,评分
    "goods_price": 11,
    "description": "",
    "month_sales": 590,月销售
    "rating_count": 91,评分比率
    "tips": "具有神秘配方浓郁的香料所散发的绝佳风味，鲜嫩多汁。",描述
    "satisfy_count": 8,好评数
    "satisfy_rate": 95,好评率
    "goods_img": "http://www.homework.com/images/slider-pic4.jpeg"
    }]}}]
     */
    //获取指定商家
    public function business(Request $request)
    {
        $business = Shop::where('id',$request->id)->first();
        //dd($business);
        $business['service_code'] = mt_rand(1,5);// 服务总评分
        $business['foods_code'] = mt_rand(1,5);// 食物总评分
        $business['high_or_low'] = true;// 低于还是高于周边商家
        $business['h_l_percent'] = mt_rand(1,100);// 低于还是高于周边商家的百分比
        $business['distance']=mt_rand(10,100);//距离
        $business['estimate_time']=mt_rand(1,10);//预计时间
        $business['evaluate'] = [[//评价
            "user_id"=> 12344,
            "username"=> "w******k",//用户名
            "user_img"=> "http://elebshop.oss-cn-beijing.aliyuncs.com/upload/PWVgUFCfS7K2cOceDVroxoPEYcGmSy7cu5k2Wsga.jpeg",
            "time"=> "2017-2-22",
            "evaluate_code"=> 1,//评分
            "send_time"=> 30,//送达时间
            "evaluate_details"=> "不怎么好吃"
        ]];
        $commodity = [];//店铺商品
        $menucategories = MenuCategory::where('shop_id',$request->id)->get();
        //dd($menucategories);
        foreach ($menucategories as $menucategory){//遍历菜品分类
            //dd($menucategory);
            $menus = Menu::where([['shop_id',$request->id],['category_id',$menucategory->id]])->get();
            //dd($menus);
            foreach ($menus as $menu){//遍历分类下的菜品
                $menu['goods_id'] = $menu['id'];//替换字段
                unset($menu['id']);//删除原字段
            }
            $commodity[] = [//替换字段
                "description" => $menucategory['description'],
                "is_selected" => $menucategory['is_selected'],
                "name" => $menucategory['name'],
                "type_accumulation" => $menucategory['type_accumulation'],
                "goods_list" => $menus
            ];
        }
        $business['commodity'] = $commodity;
        return json_encode($business);
    }

    //短信验证
    public function sms()
    {
        $tel = request()->tel;
        $params = [];

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAIEMtdSpIMbowj";
        $accessKeySecret = "2MTyCkGGsUD7GuDkFxSPlz9JSz1wiW";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "唐泽军";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140695152";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        //$code=2222;
        $code = random_int(1000, 9999);
        $params['TemplateParam'] = Array(
            "code" => $code
            //"product" => "阿里通信"
        );
        Redis::set('sms' . $tel, $code);
        Redis::expire('sms' . $tel, 300);
        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
//      dd(1);
        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );
        return [
            "status"=> "true",
            "message"=> "获取短信验证码成功"
        ];
    }

    //用户注册
    public function regist(Request $request)
    {
        //dd($request);
        //return 123;
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:members',
            'tel' => 'required|unique:members',
            'password' => 'required',
        ], [
            'username.required' => '用户名不能为空',
            'username.unique' => '用户名已存在',
            'tel.required' => '电话不能为空',
            'password.required' => '密码不能为空',
            'tel.unique' => '电话已存在',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }
        $tel = $request->tel;
        $redis = Redis::get('sms' . $tel);
        $sms = $request->sms;
        if ($redis != $sms) {
            return json_encode([
                'ststus' => 'false',
                'message' => '验证码错误',
            ]);
        }
        $password = bcrypt($request->password);
        Member::create([
            'username' => $request->username,
            'tel' => $request->tel,
            'password' => $password,
        ]);
        return json_encode([
            'status' => 'true',
            'message' => '注册成功'
        ]);
    }

    //登录验证
    public function loginCheck(Request $request)
    {
        if (Auth::attempt([
            'username' => $request->name,
            'password' => $request->password,
        ])
        ) {
            return json_encode([
                'status' => 'true',
                'message' => '登录成功',
                'id' => Auth::user()->id,
                'username' => "{$request->name}",
            ]);
        } else {
            return json_encode([
                'status' => 'false',
                'message' => '登录失败',
            ]);
        }
    }

    //地址列表
    public function addressList()
    {
        $id=Auth::user()->id;
        $addresses = Address::where('user_id',$id)->get();
        foreach ($addresses as &$v) {
            $v['area'] = $v['county'];
            $v['detail_address'] = $v['address'];
            $v['provence'] = $v['province'];
            unset($v['county']);
            unset($v['address']);
            unset($v['address']);
            unset($v['province']);
            unset($v['user_id']);
            unset($v['created_at']);
            unset($v['updated_at']);
            unset($v['is_default']);
        }
        return json_encode($addresses);
    }

    //添加地址
    public function addAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ], [
            'name.required' => '收货人姓名不能为空',
            'tel.required' => '收货人电话不能为空',
            'provence.required' => '省份不能为空',
            'city.required' => '城市不能为空',
            'area.required' => '区不能为空',
            'detail_address.required' => '详细地址不能为空',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }
        if (!preg_match('/^1[3456789]\d{9}$/', $request->tel)) {
            return [
                'status' => 'false',
                'message' => '电话不正确',
            ];
        }
        $user_id = Auth::user()->id;
        Address::create([
            'user_id' => $user_id,
            'name' => $request->name,
            'tel' => $request->tel,
            'province' => $request->provence,
            'city' => $request->city,
            'county' => $request->area,
            'address' => $request->detail_address,
            'is_default' => 0,

        ]);
        return json_encode([
            'status' => 'true',
            'message' => '添加成功',
        ]);
    }

    //指定地址接口
    public function address(Request $request)
    {
        $res = Address::where('id', '=', "{$request->id}")->get();
        return json_encode([
            'id' => $res[0]->id,
            'provence' => $res[0]->province,
            'city' => $res[0]->city,
            'area' => $res[0]->county,
            'detail_address' => $res[0]->address,
            'name' => $res[0]->name,
            'tel' => $res[0]->tel,
        ]);
    }

    // 保存修改地址接口
    public function editAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ], [
            'name.required' => '收货人姓名不能为空',
            'tel.required' => '收货人电话不能为空',
            'provence.required' => '省份不能为空',
            'city.required' => '城市不能为空',
            'area.required' => '区不能为空',
            'detail_address.required' => '详细地址不能为空',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }
        if (!preg_match('/^1[3456789]\d{9}$/', $request->tel)) {
            return [
                'status' => 'false',
                'message' => '电话不合法',
            ];
        }
        $address = Address::find($request->id);
        $address->update([
            'name' => $request->name,
            'tel' => $request->tel,
            'province' => $request->provence,
            'city' => $request->city,
            'county' => $request->area,
            'address' => $request->detail_address,
        ]);
        return json_encode([
            'status' => 'true',
            'message' => '修改成功',
        ]);
    }

    //获取购物车数据接口
    public function cart()
    {
        $goods_list = [];
        $f=0;
        $user_id = Auth::user()->id;
        $goods = Cart::where("user_id", '=', "{$user_id}")->get();
        foreach ($goods as $v) {
            $good = Menu::find($v->goods_id);
            $goods_list[]=
                [
                    'goods_id'=>$good->id,
                    'goods_name'=>$good->goods_name,
                    'goods_img'=>$good->goods_img,
                    'amount'=>$v->amount,
                    'goods_price'=>$good->goods_price,
                ];
            $f+=($v->amount)*$good->goods_price;

        }
        return[
            'goods_list'=>$goods_list,
            'totalCost'=>$f
        ];
    }

    //保存购物车接口
    public function addCart(Request $request)
    {
        $user_id = Auth::user()->id;
        Cart::where('user_id', '=', "{$user_id}")->delete();
        for ($i = 0; $i < count($request->goodsList); $i++) {
//            $data=[];
//            $data['goods_id']=$request->goodsList[$i];
//            $data['amount']=$request->goodsCount[$i];
//            $data['user_id']=$user_id;
            Cart::create([
                'goods_id' => $request->goodsList[$i],
                'amount' => $request->goodsCount[$i],
                'user_id' => $user_id,
            ]);
        }
        return json_encode([
            'status' => 'true',
            'message' => '添加成功',
        ]);
    }

    //添加订单接口
    public function addOrder(Request $request)
    {
        $user_id=Auth::user()->id;
        $goods_id=Cart::where('user_id',$user_id)->first();
        $shop_id=Menu::where('id',$goods_id->goods_id)->first();
        //dd($shop_id->shop_id);
        $sn=date('Ymd',time()).uniqid();
        $address_id=$request->address_id;
        $address=Address::where('id',$address_id)->first();
        //dd($addre);
        $status=0;
       // $created_at=time();
        $out_trade_no=uniqid();

        $goods=Cart::where('user_id',$user_id)->get();
        //dd($goods);
        $total=0;
        $goods_ids=[];
        $amounts=[];
        foreach($goods as $v){
            $goods_id=$v->goods_id;
            $amount=$v->amount;
            $goods_price=Menu::where('id',$goods_id)->first()->goods_price;
            $total+=($amount)*($goods_price);
            $goods_ids[]=$goods_id;
            $amounts[]=$amount;

        }
        DB::beginTransaction();
        try{
            $order=Order::create([
                'user_id'=>$user_id,
                'shop_id'=>$shop_id->shop_id,
                'sn'=>$sn,
                'province'=>$address->province,
                'city'=>$address->city,
                'county'=>$address->county,
                'address'=>$address->address,
                'tel'=>$address->tel,
                'name'=>$address->name,
                'total'=>$total,
                'status'=>$status,
                //'create_at'=>$created_at,
                'out_trade_no'=>$out_trade_no,
            ]);
            $order_id=$order->id;
            foreach ($goods_ids as $k=>$goods_id){
                $goods=Menu::where('id',$goods_id)->first();
            $orderGood=OrderGood::create([
                    'order_id'=>$order_id,
                    'goods_id'=>$goods_id,
                    'goods_name'=>$goods->goods_name,
                    'goods_price'=>$goods->goods_price,
                    'goods_img'=>$goods->goods_img,
                    'amount'=>$amounts[$k],
                ]);
            }
            if ($order&&$orderGood){
                DB::commit();
            }
        }catch (\Exception $e){
            DB::rollback();
        }


        return json_encode([
            "status"=> "true",
            "message"=> "添加成功",
            "order_id"=>"{$order_id}"
        ]);
    }

    //获得指定订单接口
    public function order(Request $request)
    {
        $order_id=$request->id;
        $shop_id=Order::where('id',$order_id)->first()->shop_id;

        $shops=Shop::where('id',$shop_id)->first();
        $shop_name=$shops->shop_name;
        $shop_img=$shops->shop_img;

        $orders=Order::where('id',$order_id)->first();
        $order_code=$orders->sn;
        $order_birth_time=date('Y-m-d H:i',$orders->create_at);
        $order_status=$orders->status;
        $order_price=$orders->total;
        $order_address=$orders->pronince.$orders->city.$orders->county.$orders->address;

        $goods=OrderGood::where('order_id',$order_id)->get();
        $goods_list=[];
        foreach ($goods as $good) {

            $goods_list[]=[
                'goods_id'=>$good->goods_id,
                'goods_name'=>$good->goods_name,
                'goods_img'=>$good->goods_img,
                'goods_price'=>$good->goods_price,
                'amount'=>$good->amount,
            ];
        }
        $data=[
            "id"=>$order_id,
            "order_code"=> $order_code,
            "order_birth_time"=> $order_birth_time,
            "order_status"=> $order_status,
            "shop_id"=> $shop_id,
            "shop_name"=> $shop_name,
            "shop_img"=> $shop_img,
            "goods_list"=> $goods_list,
            "order_price"=> $order_price,
            "order_address"=> $order_address
        ];
        return json_encode($data);
    }

    //获得订单列表接口
    public function orderList()
    {
        $user_id=Auth::user()->id;
        $orders=Order::where('id',$user_id)->get();
        $data=[];
        foreach ($orders as $order){
            $order_id=$order->id;
            $shop_id=Order::where('id',$order_id)->first()->shop_id;

            $shops=Shop::where('id',$shop_id)->first();
            $shop_name=$shops->shop_name;
            $shop_img=$shops->shop_img;

            $order_code=$order->sn;
            $order_birth_time=$order->created_at;
            $order_status=$order->status;
            $order_price=$order->total;
            $order_address=$order->pronince.$order->city.$order->county.$order->address;
            $goods=OrderGood::where('order_id',$order_id)->get();
            $goods_list=[];
            //dd($goods);
            foreach ($goods as $good) {
                //dd($good);
                $goods_list[]=[
                    'goods_id'=>$good->goods_id,
                    'goods_name'=>$good->goods_name,
                    'goods_img'=>$good->goods_img,
                    'goods_price'=>$good->goods_price,
                    'amount'=>$good->amount,
                ];
            }
            $data[]=[
                "id"=>$order_id,
                "order_code"=> $order_code,
                "order_birth_time"=> $order_birth_time,
                "order_status"=> $order_status,
                "shop_id"=> $shop_id,
                "shop_name"=> $shop_name,
                "shop_img"=> $shop_img,
                "goods_list"=> $goods_list,
                "order_price"=> $order_price,
                "order_address"=> $order_address
            ];

        }
        return json_encode($data);
    }

    //修改密码接口
    public function changePassword(Request $request)
    {
        $oldPassword=$request->oldPassword;
        $newPassword=bcrypt($request->newPassword);
        $user_id=Auth::user()->id;
        $dbPassword=Member::where('id',$user_id)->first()->password;

        if(!Hash::check($oldPassword,$dbPassword)){
            return json_encode([
                "status"=> "false",
                "message"=> "旧密码错误"
            ]);
        }
        DB::table('members')->where('id',$user_id)
            ->update(['password'=>$newPassword]);
        return json_encode([
            "status"=> "true",
            "message"=> "修改成功"
        ]);
    }

    //忘记密码接口
    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tel' => 'required|unique:members',
            'password' => 'required',
        ], [
            'tel.required' => '手机号码不能为空',
            'password.required' => '密码不能为空',
            'tel.unique' => '手机号码已存在',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }
        $sms = $request->sms;
        $tel = $request->tel;
        $password=bcrypt($request->password);

        $dbtel=Member::where('tel',$tel)->first();
        if($dbtel==null){
            return json_encode([
                'status' => 'false',
                'message' => '电话号码不存在',
            ])  ;
        }
        $redis = Redis::get('sms' . $tel);

        if ($redis != $sms) {
            return json_encode([
                'ststus' => 'false',
                'message' => '验证码错误',
            ]);
        }
        DB::table('members')->where('tel',$tel)
            ->update(['password'=>$password]);

        return json_encode([
            'status' => 'true',
            'message' => '重置密码成功'
        ]);
    }
}

