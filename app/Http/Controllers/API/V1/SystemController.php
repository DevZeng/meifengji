<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Advert;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class SystemController extends Controller
{
    //
    public function getAdverts()
    {
        $type = Input::get('type');
        $adverts = Advert::where('type','=',$type)->get();
        return response()->json([
            'code'=>'200',
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
}
