<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Attribute;
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
        $id = Input::get('id');
        if (empty($id)){
            $info = new CommodityInfo();
        }else{
            $info = CommodityInfo::find($id);
            if (empty($info)){
                return response()->json([
                    'code'=>'404',
                    'msg'=>'未找到！'
                ]);
            }
        }
        $info->title = Input::get('title');
        $info->description = Input::get('description');
        $info->content = Input::get('content');
        if ($info->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function listCommodityInfos(Request $request)
    {
        $page = $request->input('page',1);
        $limit = $request->input('limit',10);
        $title = $request->input('title');
        if (!empty($title)){
            $commodities = CommodityInfo::where('title','like','%'.$title.'%')->where('state','=',1)->get();
        }else{
            $commodities = CommodityInfo::where('state','=',1)->limit($limit)->offset(($page-1)*$limit)->get();
        }
        return response()->json([
            'code'=>'200',
            'data'=>$commodities
        ]);
    }
    public function delCommodityInfo($id)
    {
        $commodity = CommodityInfo::find($id);
        $commodity->state = 0;
        if ($commodity->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
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
        if (!empty($commodity)){
            $feature2 = $commodity->feature;
            $feature2 = explode(',',$feature2);
            $attrs = Attribute::whereIn('id',$feature2)->pluck('title');
            $commodity->attrs = $attrs;
        }
        return response()->json([
            'code'=>'200',
            'data'=>$commodity
        ]);
    }
}
