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
    Route::post('store/login','API\V1\UserController@storeLogin');
    Route::get('store/info','API\V1\StoreController@getStoreInfo');
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
    Route::get('accept/count','API\V1\OrderController@noAccept');
    Route::post('worker','API\V1\UserController@addWorker');
    Route::get('worker/reserves','API\V1\UserController@getMyReserves');
    Route::get('accept/reserve/{id}','API\V1\OrderController@AcceptReserve');
    Route::get('article','API\V1\SystemController@getArticle');
    Route::post('code','API\V1\SystemController@sendCode');
    Route::post('pay/notify','API\V1\OrderController@payNotify');
    Route::post('finish/reserve/{id}','API\V1\OrderController@finishReserve');
});