<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Commodity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class CommodityController extends Controller
{
    //
    public function getCommodityInfos()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $commodities = Commodity::where('state','=',1)->limit($limit)->offset(($page-1)*$limit)->get();
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

    }
}
