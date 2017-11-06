<?php

namespace App\Http\Controllers\API\V1;

use App\Libraries\WxPay;
use App\Models\Commodity;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrderSnapshot;
use App\Models\WeChatUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class OrderController extends Controller
{
    //
    public function reserve()
    {
        $uid = getUserToken(Input::get('token'));
        $address = new DeliveryAddress();
        $address->user_id = $uid;
        $address->name = Input::get('name');
        $address->number = Input::get('number');
        $address->address = Input::get('address');
        $address->latitude = Input::get('latitude');
        $address->longitude = Input::get('longitude');
        if ($address->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function makeOrder()
    {
        $uid = getUserToken(Input::get('token'));
        $user = WeChatUser::find($uid);
        $products = Input::get('products');
        if (empty($products)){
            return response()->json([
                'code'=>'400',
                'msg'=>'参数错误！'
            ]);
        }
        $number = self::makePaySn($uid);
        $price = 0;
        $order = new Order();
        $order->number = $number;
        $order->user_id = $uid;
        if ($order->save()){
            for ($i=0;$i<count($products);$i++){
                $commodity = Commodity::find($products[$i]['id']);
                $snapshot = new OrderSnapshot();
                $snapshot->number = $number;
                $snapshot->commodity_id = $commodity->commodity_id;
                $snapshot->product_id = $commodity->id;
                $snapshot->price = $commodity->price;
                $snapshot->count = $products[$i]['number'];
                $price += $commodity->price * $products[$i]['number'];
                $snapshot->save();
            }
            $order->price = $price;
            $order->save();
            $payment = new WxPay(config('wxxcx.app_id'),config('wxxcx.mch_id'),config('wxxcx.api_key'),$user->open_id);
            return response()->json([
                'code'=>'200',
                'data'=>$payment->pay($number,'购买商品',($order->price+$order->delivery_price)*100)
            ]);
        }
    }
}
