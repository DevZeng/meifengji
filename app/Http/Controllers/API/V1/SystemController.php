<?php

namespace App\Http\Controllers\API\V1;

use App\Libraries\AliSms;
use App\Libraries\AliyunSMS;
use App\Models\Advert;
use App\Models\ApplyForm;
use App\Models\Article;
use App\Models\WeChatUser;
use Illuminate\Support\Facades\DB;
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
    public function delAdvert($id)
    {
        $advert = Advert::find($id);
        if ($advert->delete()){
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
//        dd($file);
        $name = $file->getClientOriginalName();
        $name = explode('.',$name);
        if (count($name)!=2){
            return response()->json([
                'return_code'=>'FAIL',
                'return_msg'=>'非法文件名'
            ]);
        }
        $allow = \config('fileAllow');
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
    public function getArticle()
    {
        $type = Input::get('type');
        $article = Article::where('type','=',$type)->first();
        return response()->json([
            'code'=>'200',
            'data'=>$article
        ]);
    }
    public function addArticle()
    {
        $id = Input::get('id');
        if ($id){
            $article = Article::find($id);
        }else{
            $article = new Article();
        }
        $article->content = Input::get('content');
        $article->type = Input::get('type');
        if ($article->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function getArticles()
    {
        $article = Article::all();
        return response()->json([
            'code'=>'200',
            'data'=>$article
        ]);
    }
    public function sendSms($number,$code,$data)
    {
        $data = AliSms::sendSms($number,$code,$data);
//        $data = $sms->send($number,\config('alisms.company'),json_encode($data),$code);
        if($data->Code =='OK'){
            return true;
        }
//        dd($data);
        return false;
    }

    public function sendCode()
    {
        $phone = Input::get('phone');
        $code = getRandCode();
        $data = [
            'msg'=>$code
        ];
        if ($this->sendSms($phone,\config('alisms.VerificationCode'),$data)){
            setCode($phone,$code);
            return response()->json([
                'code'=>'200'
            ]);
        }
        return response()->json([
            'code'=>'500',
            'msg'=>'短信发送失败！'
        ]);
    }
    public function listApply()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $state = Input::get('state');
        $area = Input::get('area');
        $DB = DB::table('apply_forms');
        if (isset($state)){
            $DB->where('state','=',$state);
        }
        if ($area){
            $DB->where('city','like','%'.$area.'%');
        }
        $applies = $DB->limit($limit)->offset(($page-1)*$limit)->get();
        $count = $DB->count();
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$applies
        ]);
    }
    public function reviewApply($id)
    {
        $state = Input::get('state');
        if ($state==1){
            $apply = ApplyForm::find($id);
            $apply->state = 1;
            $apply->save();
            $user = WeChatUser::find($apply->user_id);
            $user->worker =1;
            $user->save();
            $smsContent = [
                'msg'=>$apply->name
            ];
            if ($this->sendSms($apply->phone,\config('alisms.Pass'),$smsContent)){
                return response()->json([
                    'code'=>'200'
                ]);
            }
            return response()->json([
                'code'=>'200'
            ]);
        }else{
            $apply = ApplyForm::find($id);
            $apply->state = 2;
            $apply->save();
            if ($this->sendSms($apply->number,\config('alisms.Fail'),['param'=>'1'])){
                return response()->json([
                    'code'=>'200'
                ]);
            }
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
}
