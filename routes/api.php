<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('test',function (){
    $sms = new \App\Libraries\AliyunSMS();
    $data = $sms->send('18664894928','美家美缝服务中心',json_encode(['param'=>'1']),'SMS_109345194');
    dd($data);
});
Route::group(['prefix'=>'v1'],function (){
    Route::post('login','API\V1\UserController@login');
    Route::get('my/reserves','API\V1\UserController@getReserves');
    Route::get('my/orders','API\V1\UserController@getOrders');
    Route::get('my/car','API\V1\UserController@getCar');
    Route::post('reserve','API\V1\OrderController@reserve');
    Route::get('adverts','API\V1\SystemController@getAdverts');
    Route::get('commodities','API\V1\CommodityController@getCommodityInfos');
    Route::get('commodity/{id}','API\V1\CommodityController@getCommodityInfo');
    Route::get('product','API\V1\CommodityController@getCommodity');
    Route::post('order','API\V1\OrderController@makeOrder');
    Route::get('confirm/{id}','API\V1\OrderController@confirm');
    Route::post('worker','API\V1\UserController@addWorker');
    Route::get('worker/reserves','API\V1\UserController@getMyReserves');
    Route::get('accept/reserve/{id}','API\V1\OrderController@AcceptReserve');
    Route::get('article','API\V1\SystemController@getArticle');
    Route::post('code','API\V1\SystemController@sendCode');
    Route::post('pay/notify','API\V1\OrderController@payNotify');
});