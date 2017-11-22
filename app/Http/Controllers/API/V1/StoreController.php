<?php

namespace App\Http\Controllers\API\V1;

use App\Models\StoreApp;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class StoreController extends Controller
{
    //
    public function getStoreInfo()
    {
        $app_id = Input::get('app_id');
        $app = StoreApp::where('app_id','=',$app_id)->where('state','=',1)->first();
        if (empty($app)){
            return response()->json([
                'code'=>'404',
                'msg'=>'该小程序不存在或已被停用！'
            ]);
        }
        $store = $app->info()->first();
        return response()->json([
            'code'=>'200',
            'data'=>$store
        ]);
    }
}
