<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Advert;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use League\Flysystem\Config;

class SystemController extends Controller
{
    //
    public function getAdverts()
    {
        $type = Input::get('type');
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        if(!empty($type)){
            $adverts = Advert::where('type','=',$type)->get();
            $count = Advert::where('type','=',$type)->count();
        }else{
            $adverts = Advert::limit($limit)->offset(($page-1)*$limit)->get();
            $count = Advert::limit($limit)->offset(($page-1)*$limit)->count();
        }
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$adverts
        ]);
    }
    public function addAdvert()
    {
        $advert = new Advert();
        $advert->type = Input::get('type');
        $advert->thumb_url = Input::get('thumb_url');
        $advert->url = Input::get('url');
        $advert->param = Input::get('param');
        if ($advert->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function uploadImage(Request $request)
    {
        if (!$request->hasFile('image')){
            return response()->json([
                'return_code'=>'FAIL',
                'return_msg'=>'空文件'
            ]);
        }
        $file = $request->file('image');
        $name = $file->getClientOriginalName();
        $name = explode('.',$name);
        if (count($name)!=2){
            return response()->json([
                'return_code'=>'FAIL',
                'return_msg'=>'非法文件名'
            ]);
        }
        $allow = Config::get('fileAllow');
        if (!in_array($name[1],$allow)){
            return response()->json([
                'return_code'=>'FAIL',
                'return_msg'=>'不支持的文件格式'
            ]);
        }
        $md5 = md5_file($file);
        $name = $name[1];
        $name = $md5.'.'.$name;
        if (!$file){
            return response()->json([
                'return_code'=>'FAIL',
                'return_msg'=>'空文件'
            ]);
        }
        if ($file->isValid()){
            $destinationPath = 'uploads';
            $size = getimagesize($file);
            $thumb = Image::make($file)->resize($size[0]*0.3,$size[1]*0.3);
            $file->move($destinationPath,$name);
            $thumb->save($destinationPath.'/thumb_'.$name);
            return response()->json([
                'return_code'=>'SUCCESS',
                'data'=>[
                    'file_name'=>$name,
                    'base_url'=>formatUrl($destinationPath.'/'.$name),
                    'thumb_url'=>formatUrl($destinationPath.'/thumb_'.$name)
                ]
            ]);
        }
    }
}
