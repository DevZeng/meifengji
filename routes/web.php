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
    Route::any('upload','API\V1\SystemController@uploadImage');
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
   Route::get('products/{id}','API\V1\CommodityController@getProducts');
   Route::get('del/product/{id}','API\V1\CommodityController@delProduct');
   Route::get('orders','API\V1\OrderController@getOrders');
   Route::post('delivery/order/{id}','API\V1\OrderController@deliveryOrder');
   Route::get('order/{id}','API\V1\OrderController@getOrder');
   Route::get('adverts','API\V1\SystemController@getAdverts');
   Route::post('advert','API\V1\SystemController@addAdvert');
   Route::get('del/advert/{id}','API\V1\SystemController@delAdvert');
   Route::post('article','API\V1\SystemController@addArticle');
   Route::get('articles','API\V1\SystemController@getArticles');
   Route::post('commodity/picture','API\V1\CommodityController@addPicture');
   Route::get('commodity/pictures/{id}','API\V1\CommodityController@getPictures');
   Route::get('del/commodity/picture/{id}','API\V1\CommodityController@delPicture');
   Route::get('applies','API\V1\SystemController@listApply');
   Route::get('review/apply/{id}','API\V1\SystemController@reviewApply');
    });
});
