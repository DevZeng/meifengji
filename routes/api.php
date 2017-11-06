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
});