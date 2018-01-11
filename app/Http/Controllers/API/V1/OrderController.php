<?php

namespace App\Http\Controllers\API\V1;

use App\Libraries\AliSms;
use App\Libraries\AliyunSMS;
use App\Libraries\WxNotify;
use App\Libraries\WxPay;
use App\Models\ApplyForm;
use App\Models\Attribute;
use App\Models\Commodity;
use App\Models\CommodityInfo;
use App\Models\DeliveryAddress;
use App\Models\Express;
use App\Models\Order;
use App\Models\OrderSnapshot;
use App\Models\Reserve;
use App\Models\StoreApp;
use App\Models\WeChatUser;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use SebastianBergmann\GlobalState\Snapshot;

class OrderController extends Controller
{
    //
    public function reserve()
    {
        $uid = getUserToken(Input::get('token'));
        $app_id = Input::get('app_id');
        $address = new DeliveryAddress();
        $address->user_id = $uid;
        $address->name = Input::get('name');
        $address->number = Input::get('number');
        $address->address = Input::get('address');
        $address->formId = Input::get('formId');
        if ($app_id){
            $app = StoreApp::where('app_id','=',$app_id)->first();
            if (!empty($app)){
                $address->app_id = $app->id;
            }
        }
//        $address->latitude = Input::get('latitude');
//        $address->longitude = Input::get('longitude');
        $address->city = Input::get('city');
        if ($address->save()){
            $applies = ApplyForm::where('city','=',$address->city)->where('state','=',1)->get();
            if(!empty($applies)){
                for ($i=0;$i<count($applies);$i++){
                    $reserves = new Reserve();
                    $reserves ->user_id = $applies[$i]->user_id;
                    $reserves ->reserve_id = $address->id;
                    $reserves->save();
                    AliSms::sendSms($applies[$i]->phone,config('alisms.Notify'),['param'=>'1']);
                }
            }
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
                'state'=>($state-1)
            ])->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
            $count = DeliveryAddress::where([
                'state'=>($state-1)
            ])->limit($limit)->offset(($page-1)*$limit)->count();
        }else{
            $reserves = DeliveryAddress::limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
            $count = DeliveryAddress::limit($limit)->offset(($page-1)*$limit)->count();
        }
        if (!empty($reserves)){
            for ($i=0;$i<count($reserves);$i++){
                $info = ApplyForm::where([
                    'user_id'=>$reserves[$i]->worker_id
                ])->first();
                $reserves[$i]->worker = empty($info)?'':$info->name;
            }
        }
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$reserves
        ]);
    }
    public function finishReserve($id)
    {
        $uid = getUserToken(Input::get('token'));
        $order = DeliveryAddress::find($id);
        if ($order->user_id != $uid){
            return response()->json([
                'code'=>'403',
                'msg'=>'无权操作！'
            ]);
        }
        $comment = Input::get('comment');
        $order->state = 2;
        $order->comment = empty($comment)?'':$comment;
        if ($order->save()){
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
        $order->comment = Input::get('comment','');
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
        if(!empty($order)){
            for ($i=0; $i<count($order);$i++){
                $order[$i]->username = WeChatUser::find($order[$i]->user_id)->nickname;

            }
        }
//        dd($order);
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
            $order->state = 2;
            if ($order->save()){
                $express = Express::where('number','=',$order->number)->first();
                if (empty($express)){
                    $express = new Express();
                }
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
        $user = WeChatUser::find($order->user_id);
        $apply = ApplyForm::where([
            'user_id'=>$uid,
            'state'=>'1'
        ])->first();
        if ($order->worker_id!=0){
            Reserve::where([
                'user_id'=>$uid,
                'reserve_id'=>$id
            ])->delete();
            return response()->json([
                'code'=>'403',
                'msg'=>'已被接单！'
            ]);
        }else{
            $order->worker_id = $uid;
            $order->state = 1;
            if ($order->save()){
                Reserve::where([
                    'user_id'=>$uid,
                    'reserve_id'=>$id
                ])->delete();
                if ($order->app_id!=0){
                    $app = StoreApp::find($order->app_id);
                    $wxnotify = new WxNotify($app->app_id,$app->secret);
                    $template_id = $app->template_id;
                }else{
                    $wxnotify = new WxNotify(config('wxxcx.app_id'),config('wxxcx.app_secret'));
                    $template_id = config('wxxcx.template_id');
                }

                $data = [
                    "touser"=>$user->open_id,
                    "template_id"=>$template_id,
                    "form_id"=> $order->formId,
                    "page"=>"pages/index/index",
                    "data"=>[
                        "keyword1"=>[
                            "value"=>date('Y-m-d H:i:s',time())
                        ],
                        "keyword2"=>[
                            "value"=>empty($apply)?'':$apply->name
                        ],
                        "keyword3"=>[
                            "value"=>empty($apply)?'':$apply->phone
                        ]
                    ]
                ];
                $wxnotify->setAccessToken();
                $data = $wxnotify->send(json_encode($data));
                return response()->json([
                    'code'=>'200'
                ]);
            }
        }
    }
    public function payNotify(Request $request)
    {
        $data = $request->getContent();
        $wx = WxPay::xmlToArray($data);
        $wspay = new WxPay(config('wxxcx.app_id'),config('wxxcx.mch_id'),config('wxxcx.api_key'),$wx['openid']);
        $data = [
            'appid'=>$wx['appid'],
            'cash_fee'=>$wx['cash_fee'],
            'bank_type'=>$wx['bank_type'],
            'fee_type'=>$wx['fee_type'],
            'is_subscribe'=>$wx['is_subscribe'],
            'mch_id'=>$wx['mch_id'],
            'nonce_str'=>$wx['nonce_str'],
            'openid'=>$wx['openid'],
            'out_trade_no'=>$wx['out_trade_no'],
            'result_code'=>$wx['result_code'],
            'return_code'=>$wx['return_code'],
            'time_end'=>$wx['time_end'],
            'total_fee'=>$wx['total_fee'],
            'trade_type'=>$wx['trade_type'],
            'transaction_id'=>$wx['transaction_id']
        ];
        $sign = $wspay->getSign($data);
        if ($sign == $wx['sign']){
            $order = Order::where(['number'=>$wx['out_trade_no']])->first();
            if ($order->state==0){
                $order->state =1;
                $snapshot = OrderSnapshot::where('number','=',$order->number)->get();
                if (!empty($snapshot)){
                    for ($i=0;$i<count($snapshot);$i++){
                        $info = CommodityInfo::find($snapshot[$i]->commodity_id);
                        $info->sales += $snapshot[$i]->count;
                        $info->save();
                        $commodity = Commodity::find($snapshot[$i]->product_id);
                        $commodity->stock -= $snapshot[$i]->count;
                        $commodity->save();
                    }
                }
                if ($order->save()){
                    return 'SUCCESS';
                }
            }

        }
        return 'ERROR';
    }
    public function addExpress($id)
    {
        $order = Order::find($id);
        $express = Express::where('number','=',$order->number)->first();
        if (empty($express)){
            $express = new Express();
        }
        $express->number = $order->number;
        $express->name = Input::get('name');
        $express->track_number = Input::get('track_number');
        if ($express->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function noAccept()
    {
        $uid = getUserToken(Input::get('token'));
        $count = Reserve::where('user_id','=',$uid)->count();
        return response()->json([
            'code'=>'200',
            'data'=>[
                'count'=>$count
            ]
        ]);
    }
}
