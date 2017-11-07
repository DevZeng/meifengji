<?php

namespace App\Http\Controllers\API\V1;

use App\Libraries\WxPay;
use App\Models\Attribute;
use App\Models\Commodity;
use App\Models\CommodityInfo;
use App\Models\DeliveryAddress;
use App\Models\Express;
use App\Models\Order;
use App\Models\OrderSnapshot;
use App\Models\Reserve;
use App\Models\WeChatUser;
use function GuzzleHttp\Psr7\uri_for;
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
//        $address->latitude = Input::get('latitude');
//        $address->longitude = Input::get('longitude');
        $address->city = Input::get('city');
        if ($address->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function getReserves()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $state = Input::get('state');
        if (!empty($state)){
            $reserves = DeliveryAddress::where([
                'state'=>$state
            ])->limit($limit)->offset(($page-1)*$limit)->get();
            $count = DeliveryAddress::where([
                'state'=>$state
            ])->limit($limit)->offset(($page-1)*$limit)->count();
        }else{
            $reserves = DeliveryAddress::limit($limit)->offset(($page-1)*$limit)->get();
            $count = DeliveryAddress::limit($limit)->offset(($page-1)*$limit)->count();
        }
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$reserves
        ]);
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
        $order->address = Input::get('address');
        $order->name = Input::get('name');
        $order->phone = Input::get('phone');
        $order->description = Input::get('description');
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
    public function confirm($id)
    {
        $uid = getUserToken(Input::get('token'));
        $order = Order::find($id);
        if (empty($order)){
            return response()->json([
                'code'=>'400',
                'msg'=>'订单不存在！'
            ]);
        }
        if ($order->user_id != $uid){
            return response()->json([
                'code'=>'403',
                'msg'=>'无权操作！'
            ]);
        }
        $order->state = 3;
        $order->save();
        return response()->json([
            'code'=>'200'
        ]);
    }
    public function getOrders()
    {
        $state = Input::get('state');
        $number = Input::get('number');
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        if (!empty($number)){
            $order = Order::where('number','=',$number)->get();
            $count = Order::where('number','=',$number)->count();
        }else{
            if (!empty($state)){
                $order = Order::where('state','=',$state)->limit($limit)->offset(($page-1)*$limit)->get();
                $count = Order::where('state','=',$state)->limit($limit)->offset(($page-1)*$limit)->count();
            }else{
                $order = Order::where('state','!=','0')->limit($limit)->offset(($page-1)*$limit)->get();
                $count = Order::where('state','!=','0')->limit($limit)->offset(($page-1)*$limit)->count();
            }
        }
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$order
        ]);
    }
    public function getOrder($id)
    {
        $order = Order::find($id);
        $snapshots = OrderSnapshot::where('number','=',$order->number)->get();
        $this->formatSnapshots($snapshots);
        $order->snapshots = $snapshots;
        return response()->json([
            'code'=>'200',
            'data'=>$order
        ]);
    }

    public function formatSnapshots($snapshots)
    {
        $length = count($snapshots);
        if ($length==0){
            return [];
        }
        for ($i=0;$i<$length;$i++){
            $info = CommodityInfo::find($snapshots[$i]->commodity_id);
            $snapshots[$i]->picture = $info->pictures()->pluck('thumb_url')->first();
            $snapshots[$i]->commodity_name = $info->title;
            $commodity = Commodity::find($snapshots[$i]->product_id);
            $feature2 = $commodity->feature;
            $feature2 = explode(',',$feature2);
            $attrs = Attribute::whereIn('id',$feature2)->pluck('title');
            $snapshots[$i]->attrs = $attrs;
        }
    }
    public function deliveryOrder($id)
    {
        $order = Order::find($id);
        if ($order->state != 1){
            return response()->json([
                'code'=>'400',
                'msg'=>'非可操作状态！'
            ]);
        }else{
            $order->state = 1;
            if ($order->save()){
                $express = new Express();
                $express->number = $order->number;
                $express->name = Input::get('name');
                $express->track_number = Input::get('track_number');
                $express->save();
                return response()->json([
                    'code'=>'200'
                ]);
            }
        }
    }
    public function AcceptReserve($id)
    {
        $uid = getUserToken(Input::get('token'));
        $order = DeliveryAddress::find($id);
        if ($order->worker!=0){
            return response()->json([
                'code'=>'403',
                'msg'=>'已被接单！'
            ]);
        }else{
            $order->worker = $uid;
            if ($order->save()){
                Reserve::where([
                    'user_id'=>$uid,
                    'reserve_id'=>$id
                ])->delete();
                return response()->json([
                    'code'=>'200'
                ]);
            }
        }
    }
}
