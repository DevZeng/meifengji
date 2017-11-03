<?php

namespace App\Http\Controllers\API\V1;

use App\Models\DeliveryAddress;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class OrderController extends Controller
{
    //
    public function reserve()
    {
        $uid = getUserToken(Input::get('token'));
        $address = new DeliveryAddress();
        $address->user_id = $uid;
        $address->name = Input::get('name');
        $address->number = Input::get('number');
        $address->address = Input::get('address');
        $address->latitude = Input::get('latitude');
        $address->longitude = Input::get('longitude');
        if ($address->save()){
            return response()->json([
                'code'=>'200'
            ]);
        }
    }
}
