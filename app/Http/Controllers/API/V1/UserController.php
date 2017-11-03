<?php

namespace App\Http\Controllers\API\V1;

use App\Libraries\Wxxcx;
use App\Models\WeChatUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
                $user->wechatId = $userinfo->openId;
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
}
