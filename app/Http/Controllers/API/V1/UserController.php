<?php

namespace App\Http\Controllers\API\V1;

use App\Libraries\Wxxcx;
use App\Models\Attribute;
use App\Models\Commodity;
use App\Models\CommodityInfo;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrderSnapshot;
use App\Models\WeChatUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class UserController extends Controller
{
    //
    public function login(Request $request)
    {
        $wxxcx = new Wxxcx(\config('wxxcx.app_id'),\config('wxxcx.app_secret'));
        $sessionKey = $wxxcx->getSessionKey($request->get('code'));
        if ($sessionKey) {
            $userinfo = $wxxcx->decode($request->get('encryptedData'), $request->get('iv'));
            $userinfo = json_decode($userinfo);
            $user = WeChatUser::where('open_id','=',$userinfo->openId)->first();
            if (empty($user)) {
                $user = new WeChatUser();
                $user->nickname = $userinfo->nickName;
                $user->gender = $userinfo->gender;
                $user->city = $userinfo->city;
                $user->province = $userinfo->province;
                $user->avatarUrl = $userinfo->avatarUrl;
                $user->open_id = $userinfo->openId;
                if ($user->save()){
                    $token = createNoncestr(16);
                    setUserToken($token,$user->id);
                    return response()->json([
                        'code'=>'200',
                        'data'=>[
                            'token'=>$token
                        ]
                    ]);
                }
            } else {
                $token = createNoncestr(16);
                setUserToken($token,$user->id);
                return response()->json([
                    'code'=>'200',
                    'data'=>[
                        'token'=>$token
                    ]
                ]);
            }
        }
        return response()->json([
            'code'=>'400',
            'msg'=>'error',
            'data'=>$wxxcx
        ]);
    }
    public function getReserves()
    {
        $uid = getUserToken(Input::get('token'));
        $state = Input::get('state',1);
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $reserves = DeliveryAddress::where([
            'user_id'=>$uid,
            'state'=>$state
        ])->limit($limit)->offset(($page-1)*$limit)->get();
        return response()->json([
            'code'=>'200',
            'data'=>$reserves
        ]);
    }
    public function getOrders()
    {
        $uid = getUserToken(Input::get('token'));
        $state = Input::get('state',1);
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $orders = Order::where([
            'user_id'=>$uid,
            'state'=>$state
        ])->limit($limit)->offset(($page-1)*$limit)->get();
        $this->formatOrder($orders);
        return response()->json([
            'code'=>'200',
            'data'=>$orders
        ]);
    }
    public function formatOrder(&$orders)
    {
        $length = count($orders);
        if ($length==0){
            return [];
        }
        for ($i=0;$i<$length;$i++){
            $snapshots = OrderSnapshot::where('number','=',$orders[$i]->number)->get();
            $this->formatSnapshots($snapshots);
            $orders[$i]->snapshots = $snapshots;
        }
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
            $commodity = Commodity::find($snapshots[$i]->product_id);
            $feature2 = $commodity->feature;
            $feature2 = explode(',',$feature2);
            $attrs = Attribute::whereIn('id',$feature2)->pluck('title');
            $snapshots[$i]->attrs = $attrs;
        }
    }
    public function adminLogin()
    {
        $username = Input::get('username');
        $password = Input::get('password');
        if (Auth::attempt(['username'=>$username,'password'=>$password],true)){
            return response()->json([
                'code'=>'200'
            ]);
        }else{
            return response()->json([
                'code'=>'500',
                'msg'=>'用户名或密码错误！'
            ]);
        }
    }
}
