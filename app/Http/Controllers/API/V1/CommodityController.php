<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Commodity;
use App\Models\CommodityInfo;
use App\Models\Standard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class CommodityController extends Controller
{
    //
    public function getCommodityInfos(Request $request)
    {
        $page = $request->input('page',1);
        $limit = $request->input('limit',10);
        $title = $request->input('title');
        if (!empty($title)){
            $commodities = CommodityInfo::where('title','like','%'.$title.'%')->get();
        }else{
            $commodities = CommodityInfo::where('state','=',1)->limit($limit)->offset(($page-1)*$limit)->get();
        }
        if (!empty($commodities)){
            for ($i=0;$i<count($commodities);$i++){
                $commodities[$i]->price = $commodities[$i]->commodities()->orderBy('price','asc')->pluck('price')->first();
                $commodities[$i]->picture = $commodities[$i]->pictures()->pluck('thumb_url')->first();
            }
        }
        return response()->json([
            'code'=>'200',
            'data'=>$commodities
        ]);
    }

    public function addCommodityInfo()
    {

    }

    public function addStandard()
    {

    }
    public function getStandards()
    {
        $standards = Standard::all();
    }
    public function getCommodityInfo($id)
    {
        $info = CommodityInfo::find($id);
        $info->pictures = $info->pictures()->pluck('thumb_url');
        if (empty($info)){
            return response()->json([
                'code'=>'404',
                'msg'=>'未找到该商品！'
            ]);
        }
        $info->price = $info->commodities()->orderBy('price','ASC')->pluck('price')->first();
        $standards = $info->standards()->get();
        if (!empty($standards)){
            for ($i=0;$i<count($standards);$i++){
                $standards[$i]->attrs = $standards[$i]->attr()->get();
            }
        }
        $info->standards = $standards;
        return response()->json([
            'code'=>'200',
            'data'=>$info
        ]);
    }
    public function getCommodity()
    {
        $feature = Input::get('feature');
        if (empty($feature)){
            return response()->json([
                'code'=>'400',
                'msg'=>'参数错误！'
            ]);
        }
        $feature = json_decode($feature);
        sort($feature);
        $feature = implode(',',$feature);
        $commodity = Commodity::where('feature','=',$feature)->first();
        return response()->json([
            'code'=>'200',
            'data'=>$commodity
        ]);
    }
}
