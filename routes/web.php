<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::group(['middleware'=>'cross'],function (){
    Route::post('login','API\V1\UserController@adminLogin');
    Route::group(['middleware'=>'auth'],function (){
//   Route::post();
   Route::get('reserves','API\V1\OrderController@getReserves');
   Route::post('info','API\V1\CommodityController@addCommodityInfo');
   Route::get('infos','API\V1\CommodityController@listCommodityInfos');
   Route::get('del/info/{id}','API\V1\CommodityController@delCommodityInfo');
   Route::post('standard','API\V1\CommodityController@addStandard');
   Route::get('standards/{id}','API\V1\CommodityController@getStandards');
   Route::get('del/standard/{id}','API\V1\CommodityController@delStandard');
   Route::post('product','API\V1\CommodityController@addProduct');
   Route::get('products','API\V1\CommodityController@getProducts');
   Route::get('del/product/{id}','API\V1\CommodityController@delProduct');
    });
});
