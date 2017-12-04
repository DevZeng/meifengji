<?php

namespace App\Http\Controllers\API\V1;

use App\Libraries\Wxxcx;
use App\Models\ApplyForm;
use App\Models\Article;
use App\Models\Attribute;
use App\Models\Commodity;
use App\Models\CommodityInfo;
use App\Models\CommodityPicture;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrderSnapshot;
use App\Models\Reserve;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\StoreApp;
use App\Models\WeChatUser;
use App\User;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class UserController extends Controller
{
    //
    public function storeLogin(Request $request)
    {
        $app_id = $request->get('app_id');
        $app = StoreApp::where('app_id','=',$app_id)->first();
        $wxxcx = new Wxxcx($app->app_id,$app->secret);
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
                            'worker'=>$user->worker,
                            'apply'=>0,
                            'token'=>$token,
                            'enable'=>$user->enable
                        ]
                    ]);
                }
            } else {
                $token = createNoncestr(16);
                setUserToken($token,$user->id);
                $count = ApplyForm::where('user_id','=',$user->id)->where('state','=','0')->count();
                return response()->json([
                    'code'=>'200',
                    'data'=>[
                        'worker'=>$user->worker,
                        'apply'=>$count,
                        'token'=>$token,
                        'enable'=>$user->enable
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
                            'worker'=>$user->worker,
                            'apply'=>0,
                            'token'=>$token,
                            'enable'=>$user->enable
                        ]
                    ]);
                }
            } else {
                $token = createNoncestr(16);
                setUserToken($token,$user->id);
                $count = ApplyForm::where('user_id','=',$user->id)->where('state','=','1')->count();
                return response()->json([
                    'code'=>'200',
                    'data'=>[
                        'worker'=>$user->worker,
                        'apply'=>$count,
                        'token'=>$token,
                        'enable'=>$user->enable
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
        ])->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        if (!empty($reserves)){
            for ($i=0;$i<count($reserves);$i++){
                $reserves[$i]->worker = ApplyForm::where('user_id','=',$reserves[$i]->worker_id)->first();
            }
        }
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
        ])->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
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
            $orders[$i]->express = $orders[$i]->express()->first();
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
            $snapshots[$i]->commodity_name = $info->title;
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
            $role_id = RoleUser::where('user_id','=',Auth::id())->pluck('role_id')->first();
            $role = Role::find($role_id);
            return response()->json([
                'code'=>'200',
                'data'=>[
                    'role'=>$role->name
                ]
            ]);
        }else{
            return response()->json([
                'code'=>'500',
                'msg'=>'用户名或密码错误！'
            ]);
        }
    }
    public function addWorker()
    {
        $uid = getUserToken(Input::get('token'));
        $phone = Input::get('phone');
        $password = Input::get('password');
        $good_at = Input::get('good_at');
        $address = Input::get('address');
        $name = Input::get('name');
        $id_card = Input::get('id_card');
        $city = Input::get('city');
        $sms = Input::get('sns');
        $code = getCode($phone);
        if (empty($sms)){
            return response()->json([
                'code'=>'400',
                'msg'=>'请输入验证码！'
            ]);
        }
        if ($sms!=$code){
            return response()->json([
                'code'=>'400',
                'msg'=>'验证码错误！'
            ]);
        }
//        $lat = Input::get('lat');
//        $lng = Input::get('lng');
        $count = ApplyForm::where('phone','=',$phone)->where('state','=','1')->count();
        $id_count = ApplyForm::where('id_card','=',$id_card)->where('state','=','1')->count();
        if ($count!=0){
            return response()->json([
                'code'=>'400',
                'msg'=>'手机号或证件号已被使用！'
            ]);
        }
        if ($id_count!=0){
            return response()->json([
                'code'=>'400',
                'msg'=>'手机号或证件号已被使用！'
            ]);
        }

            $apply = new ApplyForm();
            $apply->user_id = $uid;
            $apply->phone = $phone;
            $apply->good_at = $good_at;
            $apply->address = $address;
            $apply->name = $name;
            $apply->id_card = $id_card;
            $apply->city = $city;
//            $apply->lat = $lat;
//            $apply->lng = $lng;
            if ($apply->save()){
                $user = new User();
                $user->username = $phone;
                $user->name = $name;
                $user->password = bcrypt($password);
                $user->save();
                return response()->json([
                    'code'=>'200'
                ]);
            }
    }
    public function modifyApply($id)
    {
        $apply = ApplyForm::find($id);
        if (empty($apply)){
            return response()->json([
                'code'=>'404',
                'msg'=>'Not Found'
            ]);
        }
        $phone = Input::get('phone');
        $name = Input::get('name');
        $apply->phone = empty($phone)?$apply->phone:$phone;
        $apply->name = empty($name)?$apply->name:$name;
        if ($apply->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function delWorker($id)
    {
        $apply = ApplyForm::find($id);
        if (empty($apply)){
            return response()->json([
                'code'=>'404',
                'msg'=>'Not Found'
            ]);
        }
        $user = WeChatUser::find($apply->user_id);
        $user->worker = 0;
        $user->enable = 1;
        $user->save();
        if ($apply->delete()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function getMyReserves()
    {
        $uid = getUserToken(Input::get('token'));
        $type = Input::get('type');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        if ($type ==1){
            $id = Reserve::where('user_id','=',$uid)->limit($limit)->offset(($page-1)*$limit)->pluck('reserve_id');
            $reserves = DeliveryAddress::whereIn('id',$id)->orderBy('id','DESC')->get();
        }else{
            $reserves = DeliveryAddress::where('worker_id','=',$uid)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        }
        return response()->json([
            'code'=>'200',
            'data'=>$reserves
        ]);
    }
    public function getWorkers()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $name = Input::get('name');
        $idcard = Input::get('id_card');
        $province = Input::get('province');
        $start = Input::get('start');
        $end = Input::get('end');
        $search = Input::get('search');
        if ($search){
            $applyDb = ApplyForm::where('state','=',1);
            if ($name){
                $applyDb->where('name','like','%'.$name.'%');
            }
            if ($province){
                $applyDb->where('city','like','%'.$province.'%');
            }
            if ($idcard){
                $applyDb->where('id_card','like','%'.$idcard.'%');
            }
            if ($start){
                $applyDb->where('created_at','>',$start)->where('created_at','<',$end);
            }
            $ids = $applyDb->pluck('user_id');
//            dd($ids);
            $count = WeChatUser::whereIn('id',$ids)->count();
            $workers = WeChatUser::whereIn('id',$ids)->limit($limit)->offset(($page-1)*$limit)->get();
        }else{
            $count = WeChatUser::where('worker','=',1)->count();
            $workers = WeChatUser::where('worker','=',1)->limit($limit)->offset(($page-1)*$limit)->get();
        }

        if (!empty($workers)){
            for ($i=0;$i<count($workers);$i++){
                $workers[$i]->apply = $workers[$i]->apply()->first();
                $workers[$i]->count = DeliveryAddress::where('worker_id','=',$workers[$i]->id)->count();
            }
        }
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$workers
        ]);
    }
    public function modifyWorker($id)
    {
        $worker = WeChatUser::find($id);
        $enable = $worker->enable;
        $worker->enable = ($enable==0)?1:0;
        $worker->save();
        return response()->json([
            'code'=>'200'
        ]);
    }
    public function addUser()
    {
        $user = new User();
        $count = User::where('username','=',Input::get('username'))->count();
        if ($count!=0){
            return response()->json([
                'code'=>'400',
                'msg'=>'该用户已存在！'
            ]);
        }
        $app_count = StoreApp::where('name','=',Input::get('name'))->count();
        if ($app_count!=0){
            return response()->json([
                'code'=>'400',
                'msg'=>'该加盟商已存在！'
            ]);
        }
        $user->username = Input::get('username');
        $user->password = bcrypt(Input::get('password'));
        $user->name = Input::get('name');
        if ($user->save()){
            $role = new RoleUser();
            $role->user_id = $user->id;
            $role->role_id = 2;
            $role->save();
            $app = new StoreApp();
            $app->user_id = $user->id;
            $app->name = Input::get('name');
            $app->app_id = Input::get('app_id');
            $app->secret = Input::get('secret');
            $app->template_id = Input::get('template_id');
            if ($app->save()){
                return response()->json([
                    'code'=>'200'
                ]);
            }
        }
    }
    public function delApp($id)
    {
        $app = StoreApp::find($id);
        $state = $app->state;
        $app->state = ($state == 0)?1:0;
//        $user = User::find($app->user_id);
//        $user->delete();
//        RoleUser::where('user_id','=',$app->user_id)->delete();
        if ($app->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function modifyPassword($id)
    {
        $app = StoreApp::find($id);
        $user = User::find($app->user_id);
        $password = Input::get('password');
        if (!empty($password)){
            $user->password = bcrypt($password);
        }
        if ($user->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function listApps()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $appsDb = DB::table('store_apps');
        $name = Input::get('name');
        $app_id = Input::get('app_id');
        if ($name){
            $appsDb->where('name','like','%'.$name.'%');
        }
        if ($app_id){
            $appsDb->where('app_id','=',$app_id);
        }
        $apps = $appsDb->limit($limit)->offset(($page-1)*$limit)->get();
        if (!empty($apps)){
            for ($i=0;$i<count($apps);$i++){
                $apps[$i]->user = User::find($apps[$i]->user_id);
            }
        }
        $count = $appsDb->count();
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$apps
        ]);
    }
}
