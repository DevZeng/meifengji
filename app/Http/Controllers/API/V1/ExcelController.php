<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Advert;
use App\Models\ApplyForm;
use App\Models\Article;
use App\Models\CommodityInfo;
use App\Models\CommodityPicture;
use App\Models\DeliveryAddress;
use App\Models\InfoPic;
use App\Models\OrderSnapshot;
use App\Models\WeChatUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Excel;

class ExcelController extends Controller
{
    //
    public function Output()
    {
        $table = Input::get('table');
        if (!$table){
            return response()->json([
                'code'=>'400',
                'message'=>'参数错误！'
            ]);
        }
        $state = Input::get('state');
        $start = Input::get('start');
        $end = Input::get('end');
        switch ($table){
            case 'reverse':
                $dbObj = DB::table('delivery_addresses');
                $origin = [['日期','姓名','联系方式','地址','接单师傅','当前状态']];
                $name = '预约';
                if ($state){
                    $dbObj->where('state','=',$state-1);
                }
                if ($start){
                    $dbObj->where('created_at','>',$start)->where('created_at','<',$end);
                }
                $data = $dbObj->get();
                $data = $this->formatReverse($data);
                break;
            case 'order':
                $dbObj = DB::table('orders');
                $origin = [['订单号','日期','姓名','联系方式','购买商品','购买价格','地址','状态']];
                $name = '成交';
                if ($state){
                    $dbObj->where('state','=',$state-1);
                }
                if ($start){
                    $dbObj->where('created_at','>',$start)->where('created_at','<',$end);
                }
                $data = $dbObj->get();
                $data = $this->formatOrder($data);
                break;
            case 'worker':
                $dbObj = DB::table('we_chat_users')->where('worker','=',1);
                $origin = [['姓名','联系方式','接单数量','注册区域','注册时间','使用状态']];
                $name = '师傅列表';
                if ($state){
                    $dbObj->where('enable','=',$state-1);
                }
                if ($start){
                    $dbObj->where('created_at','>',$start)->where('created_at','<',$end);
                }
                $data = $dbObj->get();
                $data = $this->formatWorker($data);
//                dd($data);
                break;
        }
        $final = array_merge($origin,$data);
//        return response()->json([
//            'code'=>200,
//            'data'=>$final
//        ]);
        \Maatwebsite\Excel\Facades\Excel::create($name,function ($excel) use ($final){
            $excel->sheet('sheet1',function ($sheet) use ($final){
                $count = count($final);
                for ($j=0;$j<$count;$j++){
                    $sheet->row($j+1,$final[$j]);
                }
            });
        })->export('xlsx');
    }
    public function formatReverse($reverses)
    {
        $ret_arr = [];
        $state = ['未接单','已受理','已完成'];
        if (!empty($reverses)){
            $count = count($reverses);
            for ($i=0;$i<$count;$i++){
                if ($reverses[$i]->worker_id){
                    $apply = ApplyForm::where('user_id','=',$reverses[$i]->worker_id)->pluck('name')->first();
                }else{
                    $apply = '未接单';
                }

                $swap = [
                    date('Y-m-d H:i:s',strtotime($reverses[$i]->created_at)),
                    (string)$reverses[$i]->name,
                    (string)$reverses[$i]->number,
                    (string)$reverses[$i]->address,
                    (string)$apply,
                    (string)$state[$reverses[$i]->state]
                ];
                array_push($ret_arr,$swap);
            }
        }
        return $ret_arr;
    }
    public function formatOrder($orders)
    {
        $ret_arr = [];
        $state = ['未付款','待发货','待收货','已完成'];
        if (!empty($orders)){
            $count = count($orders);
            for ($i=0;$i<$count;$i++){
                $snap = OrderSnapshot::where('number','=',$orders[$i]->number)->get();
                $commodity = '';
                if (!empty($snap)){
                    for ($j=0;$j<count($snap);$j++){
                        $commodity .= CommodityInfo::find($snap[$j]->commodity_id)->title.' x'.$snap[$j]->count.',';
                    }
                }
                $swap = [
                    date('Y-m-d H:i:s',strtotime($orders[$i]->created_at)),
                    (string)$orders[$i]->name,
                    (string)$orders[$i]->phone,
                    (string)$commodity,
                    (string)$orders[$i]->price,
                    (string)$orders[$i]->address,
                    (string)$state[$orders[$i]->state]
                ];
                array_push($ret_arr,$swap);
            }
        }
        return $ret_arr;
    }
    public function formatWorker($workers)
    {
        $ret_arr = [];
        $state = ['停用','正常使用'];
        if (!empty($workers)){
            $count = count($workers);
//            dd($count);
            for ($i=0;$i<$count;$i++){
//                echo $count;
                $apply = ApplyForm::where('user_id','=',$workers[$i]->id)->where('state','=',1)->first();
                $d_count = DeliveryAddress::where('worker_id','=',$workers[$i]->id)->count();
                $swap = [
                    empty($apply)?"":(string)$apply->name,
                    empty($apply)?"":(string)$apply->phone,
                    (string)$d_count,
                    empty($apply)?"":(string)$apply->city,
                    date('Y-m-d H:i:s',strtotime($workers[$i]->created_at)),
                    (string)$state[$workers[$i]->enable],
                ];
                array_push($ret_arr,$swap);
            }
        }
        return $ret_arr;
    }
    public function test()
    {
        $adverts = Advert::all();
        if (!empty($adverts)){
            for ($i=0;$i<count($adverts);$i++){
                $adverts[$i]->thumb_url = str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$adverts[$i]->thumb_url);
                $adverts[$i]->url = str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$adverts[$i]->url);
                $adverts[$i]->save();
            }
        }
    }
    public function test1()
    {
        $article = Article::all();
        if (!empty($article)){
            for ($i=0;$i<count($article);$i++){
                $article[$i]->content = str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$article[$i]->content);
                $article[$i]->save();
            }
        }
    }
    public function test2()
    {
        $infos = CommodityInfo::all();
        if (!empty($infos)){
            for ($i=0;$i<count($infos);$i++) {
                $infos[$i]->cover = empty($infos[$i]->cover)?'':str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$infos[$i]->cover);
                $infos[$i]->content = empty($infos[$i]->content)?'':str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$infos[$i]->content);
                $infos[$i]->save();
            }
        }
    }
    public function test3()
    {
        $pics = CommodityPicture::all();
        if (!empty($pics)){
            for ($i=0;$i<count($pics);$i++){
                $pics[$i]->url = str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$pics[$i]->url);
                $pics[$i]->thumb_url = str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$pics[$i]->thumb_url);
                $pics[$i]->save();
            }
        }
    }
    public function test4()
    {
        $pics = InfoPic::all();
        if (!empty($pics)){
            for ($i=0;$i<count($pics);$i++){
                $pics[$i]->url = str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$pics[$i]->url);
                $pics[$i]->thumb_url = str_replace('https://xcx.xiashantown.cn','https://xcx.gdmeika.com',$pics[$i]->thumb_url);
                $pics[$i]->save();
            }
        }
    }
}
