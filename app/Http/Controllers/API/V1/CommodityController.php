<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Attribute;
use App\Models\Commodity;
use App\Models\CommodityInfo;
use App\Models\CommodityPicture;
use App\Models\Standard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Rules\In;

class CommodityController extends Controller
{
    //
    public function getCommodityInfos(Request $request)
    {
        $page = $request->input('page',1);
        $limit = $request->input('limit',10);
        $title = $request->input('title');
        if (!empty($title)){
            $commodities = CommodityInfo::where('state','=',1)->where('title','like','%'.$title.'%')->orderBy('id','DESC')->get();
        }else{
            $commodities = CommodityInfo::where('state','=',1)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        }
        if (!empty($commodities)){
            for ($i=0;$i<count($commodities);$i++){
//                $commodities[$i]->price = $commodities[$i]->commodities()->orderBy('price','asc')->pluck('price')->first();
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
        $info->cover = Input::get('cover');
        $unit = Input::get('unit');
        $info->unit = empty($unit)?'':$unit;
        $info->sales = Input::get('sales',0);
        $info->price = Input::get('price');
        if ($info->save()){
//            CommodityPicture::where('commodity_id','=',$info->id)->delete();
            $images = Input::get('images');
            if (!empty($images)){
                foreach ($images as $image){
                    $img = new CommodityPicture();
                    $path = 'uploads/';
                    $img->commodity_id = $info->id;
                    $img->product_id = 0;
                    $img->thumb_url = formatUrl($path.'thumb_'.$image);
                    $img->url = formatUrl($path.$image);
                    $img->save();
                }
            }
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
            $commodities = CommodityInfo::where('title','like','%'.$title.'%')->where('state','=',1)->orderBy('id','DESC')->get();
            $count = CommodityInfo::where('title','like','%'.$title.'%')->where('state','=',1)->count();
        }else{
            $commodities = CommodityInfo::where('state','=',1)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
            $count = CommodityInfo::where('state','=',1)->limit($limit)->offset(($page-1)*$limit)->count();
        }
        if (!empty($commodities)){
            for ($i=0;$i<count($commodities);$i++){
                $commodities[$i]->pictures = $commodities[$i]->pictures()->where('product_id','=',0)->get();
            }
        }
        return response()->json([
            'code'=>'200',
            'count'=>$count,
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

    public function addStandard($id)
    {
        $title = Input::get('title');
        $standard = Standard::find($id);
        $standard->title = $title;
        if ($standard->save()){
            $attrs = Input::get('attrs');
            if (!empty($attrs)){
                foreach ($attrs as $attr){
                    $attribute = new Attribute();
                    $attribute->title = $attr;
                    $attribute->standard_id = $standard->id;
                    $attribute->save();
                }
            }
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function delAttr($id)
    {
        $attr = Attribute::find($id);
        $attr->state = 0;
        if ($attr->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function addStandards()
    {
        $commodity_id = Input::get('commodity_id');
        $standards = Input::get('standards');
        if (empty($standards)){
            return response()->json([
                'code'=>'400',
                'msg'=>'参数不能为空！'
            ]);
        }
        foreach ($standards as $standard){
            $stan = new Standard();
            $stan->title = $standard['title'];
            $stan->commodity_id = $commodity_id;
            $stan->save();
            if (!empty($standard['attrs'])){
                foreach ($standard['attrs'] as $attr){
                    $attribute = new Attribute();
                    $attribute->title = $attr;
                    $attribute->standard_id = $stan->id;
                    $attribute->save();
                }
            }
        }
        return response()->json([
            'code'=>'200'
        ]);
    }
    public function getStandards($id)
    {
        $standards = Standard::where([
            'commodity_id'=>$id,
            'state'=>1
        ])->get();
        if (!empty($standards)){
            for ($i=0;$i<count($standards);$i++){
                $standards[$i]->commodity_title = CommodityInfo::find($standards[$i]->commodity_id)->title;
                $standards[$i]->attrs = $standards[$i]->attr()->where('state','=',1)->get();
            }
        }
        return response()->json([
            'code'=>'200',
            'data'=>$standards
        ]);
    }
    public function delStandard($id)
    {
        $standard = Standard::find($id);
        $standard->state = 0;
        if ($standard->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function getCommodityInfo($id)
    {
        $info = CommodityInfo::find($id);
        $info->pictures = $info->pictures()->where('product_id','=',0)->get();
        if (empty($info)){
            return response()->json([
                'code'=>'404',
                'msg'=>'未找到该商品！'
            ]);
        }
        $info->price = $info->commodities()->orderBy('price','ASC')->pluck('price')->first();
        $standards = $info->standards()->where('state','=',1)->get();
        if (!empty($standards)){
            for ($i=0;$i<count($standards);$i++){
                $standards[$i]->attrs = $standards[$i]->attr()->where('state','=',1)->get();
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
            $commodity->picture = $commodity->pictures()->get();
        }
        return response()->json([
            'code'=>'200',
            'data'=>$commodity
        ]);
    }
    public function addProduct()
    {
        $id = Input::get('id');
        $feature = Input::get('feature');
        $commodity_id = Input::get('commodity_id');
        sort($feature);
        $feature = implode(',',$feature);
        if (empty($id)){
            $count = Commodity::where([
                'feature'=>$feature,
                'commodity_id'=>$commodity_id
            ])->count();
            if ($count != 0) {
                return response()->json([
                    'code'=>'400',
                    'msg'=>'该规格商品已存在！'
                ]);
            }
            $commodity = new Commodity();
            $commodity->feature = $feature;
            $commodity->price = Input::get('price');
            $commodity->stock = Input::get('stock');
            $commodity->commodity_id = $commodity_id;
        }else{
            $commodity = Commodity::find($id);
            $commodity->feature = $feature;
            $commodity->price = Input::get('price');
            $commodity->stock = Input::get('stock');
        }
        if ($commodity->save()){
            $image = Input::get('image');
            if (!empty($image)){
                CommodityPicture::where([
                    'commodity_id'=>$commodity->commodity_id,
                    'product_id'=>$commodity->id
                ])->delete();
                $picture = new CommodityPicture();
                $path = 'uploads/';
                $picture->commodity_id = $commodity->commodity_id;
                $picture->product_id = $commodity->id;
                $picture->url = formatUrl($path.$image);
                $picture->thumb_url = formatUrl($path.'thumb_'.$image);
                $picture->save();
            }
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function delProduct($id)
    {
        $commodity = Commodity::find($id);
        $commodity->state = 0;
        if ($commodity->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function getProducts($id)
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $commodities = Commodity::where([
            'commodity_id'=>$id,
            'state'=>1
        ])->limit($limit)->offset(($page-1)*$limit)->get();
        $count = Commodity::where([
            'commodity_id'=>$id,
            'state'=>1
        ])->limit($limit)->offset(($page-1)*$limit)->count();
        if (!empty($commodities)){
            for ($i=0;$i<count($commodities);$i++){
                $feature = $commodities[$i]->feature;
                $feature = explode(',',$feature);
                $featureText = Attribute::whereIn('id',$feature)->get();
                $commodities[$i]->attrs = $featureText;
                $commodities[$i]->title = CommodityInfo::find($commodities[$i]->commodity_id)->title;
                $commodities[$i]->pictures = $commodities[$i]->pictures()->first();
            }
        }
        return response()->json([
            'code'=>'200',
            'count'=>$count,
            'data'=>$commodities
        ]);
    }
    public function addPicture()
    {
        $commodity_id = Input::get('commodity_id');
        $commodity = Commodity::find($commodity_id);
        $picture = new CommodityPicture();
        $picture->product_id = $commodity->id;
        $picture->commodity_id = $commodity->commodity_id;
        $picture->url = Input::get('base_url');
        $picture->thumb_url = Input::get('thumb_url');
        if ($picture->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
    public function getPictures($id)
    {
        $commodity = Commodity::find($id);
        $pictures = $commodity->pictures()->get();
        return response()->json([
            'code'=>'200',
            'data'=>$pictures
        ]);
    }
    public function delPicture($id)
    {
        $picture = CommodityPicture::find($id);
        if ($picture->delete()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
}
