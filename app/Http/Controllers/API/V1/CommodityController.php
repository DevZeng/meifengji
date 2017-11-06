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
        $page = $request->get('page',1);
        $limit = $request->get('limit',10);
        $title = $request->get('title');
        if (!empty($title)){
            echo 'title';
            $commodities = CommodityInfo::where('title','like','%'.$title.'%')->get();
        }else{
            echo 'no title';
            $commodities = CommodityInfo::where('state','=',1)->limit($limit)->offset(($page-1)*$limit)->get();
        }
        if (!empty($commodities)){
            for ($i=0;$i<count($commodities);$i++){
                $commodities[$i]->price = $commodities[$i]->commodities()->orderBy('price','asc')->pluck('price')->first();
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
}
