<?php

namespace App\Http\Controllers\API\V1;

use App\Models\InfoPic;
use App\Models\StoreApp;
use App\Models\StoreInfo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
        if (!empty($store)){
            $store->pic = $store->pic()->where('type','=',1)->get();
            $store->pic2 = $store->pic()->where('type','=',2)->get();
        }
        return response()->json([
            'code'=>'200',
            'data'=>$store
        ]);
    }
    public function addStoreInfo()
    {
        $uid = Auth::id();
        $app = StoreApp::where('user_id','=',$uid)->first();
        $info = StoreInfo::where('app_id','=',$app->id)->first();
        if (empty($info)){
            $info = new StoreInfo();
            $info->app_id = $app->id;
        }
        $info->name = Input::get('name');
        $info->phone = Input::get('phone');
        $info->address = Input::get('address');
        $info->detail	 = Input::get('detail');
        $info->lat = Input::get('lat');
        $info->lng = Input::get('lng');
        if ($info->save()){
            $pic = Input::get('pic');
            $pic2 = Input::get('pic2');
            $destinationPath = 'uploads';
            if (!empty($pic)){
                InfoPic::where([
                    'info_id'=>$info->id,
                    'type'=>1
                ])->delete();
                foreach ($pic as $item){
                    $picture = new InfoPic();
                    $picture->info_id = $info->id;
                    $picture->url = formatUrl($destinationPath.'/'.$item);
                    $picture->name = $item;
                    $picture->thumb_url = formatUrl($destinationPath.'/thumb_'.$item);
                    $picture->type=1;
                    $picture->save();
                }
            }
            if (!empty($pic2)){
                InfoPic::where([
                    'info_id'=>$info->id,
                    'type'=>2
                ])->delete();
                foreach ($pic2 as $item){
                    $picture = new InfoPic();
                    $picture->info_id = $info->id;
                    $picture->url = formatUrl($destinationPath.'/'.$item);
                    $picture->name = $item;
                    $picture->thumb_url = formatUrl($destinationPath.'/thumb_'.$item);
                    $picture->type=2;
                    $picture->save();
                }
            }
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function getInfo()
    {
        $uid = Auth::id();
        $app = StoreApp::where('user_id','=',$uid)->first();
        $info = StoreInfo::where('app_id','=',$app->id)->first();
        if (!empty($info)){
            $info->pic = $info->pic()->where('type','=',1)->get();
            $info->pic2 = $info->pic()->where('type','=',2)->get();
        }
        return response()->json([
            'code'=>'200',
            'data'=>$info
        ]);
    }
    public function delStoreApp($id)
    {
        $app = StoreApp::find($id);
        $app->state = 0;
        $app->save();
        return response()->json([
            'code'=>'200'
        ]);
    }
    public function modifyStoreApp($id)
    {
        $app = StoreApp::find($id);
        if (empty($app)){
            return response()->json([
                'code'=>'404',
                'msg'=>"Not Found"
            ]);
        }
        $app_id = Input::get('app_id');
        $secret = Input::get('secret');
        $template_id = Input::get('template_id');
        $name = Input::get('name');
        $app->app_id = empty($app_id)?$app->app_id:$app_id;
        $app->secret = empty($secret)?$app->secret:$secret;
        $app->template_id = empty($template_id)?$app->template_id:$template_id;
        $app->name = empty($name)?$app->name:$name;
        if ($app->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
}
