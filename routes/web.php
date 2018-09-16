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
Route::get('test','API\V1\ExcelController@test');
Route::get('test1','API\V1\ExcelController@test1');
Route::get('test2','API\V1\ExcelController@test2');
Route::get('test3','API\V1\ExcelController@test3');
Route::get('test4','API\V1\ExcelController@test4');
Route::group(['middleware'=>'cross'],function (){
    Route::post('login','API\V1\UserController@adminLogin');
    Route::get('workers','API\V1\UserController@getWorkers');
    Route::get('modify/worker/{id}','API\V1\UserController@modifyWorker');
    Route::post('modify/apply/{id}','API\V1\UserController@modifyApply');
    Route::get('del/apply/{id}','API\V1\UserController@delWorker');
    Route::any('upload','API\V1\SystemController@uploadImage');
    Route::group(['middleware'=>'auth'],function (){
//   Route::post();
   Route::get('reserves','API\V1\OrderController@getReserves')->middleware('role:admin');
   Route::post('commodity/info','API\V1\CommodityController@addCommodityInfo')->middleware('role:admin');
   Route::get('infos','API\V1\CommodityController@listCommodityInfos')->middleware('role:admin');
   Route::get('del/info/{id}','API\V1\CommodityController@delCommodityInfo')->middleware('role:admin');
   Route::post('standards','API\V1\CommodityController@addStandards')->middleware('role:admin');
   Route::post('standard/{id}','API\V1\CommodityController@addStandard')->middleware('role:admin');
   Route::get('standards/{id}','API\V1\CommodityController@getStandards')->middleware('role:admin');
   Route::get('del/standard/{id}','API\V1\CommodityController@delStandard')->middleware('role:admin');
   Route::get('del/attr/{id}','API\V1\CommodityController@delAttr')->middleware('role:admin');
   Route::post('product','API\V1\CommodityController@addProduct')->middleware('role:admin');
   Route::get('products/{id}','API\V1\CommodityController@getProducts')->middleware('role:admin');
   Route::get('del/product/{id}','API\V1\CommodityController@delProduct')->middleware('role:admin');
   Route::get('orders','API\V1\OrderController@getOrders')->middleware('role:admin');
   Route::post('delivery/order/{id}','API\V1\OrderController@deliveryOrder')->middleware('role:admin');
   Route::get('order/{id}','API\V1\OrderController@getOrder')->middleware('role:admin');
//   Route::post('order/{id}','API\V1\OrderController@getOrder');
   Route::get('adverts','API\V1\SystemController@getAdverts')->middleware('role:admin');
   Route::post('advert','API\V1\SystemController@addAdvert')->middleware('role:admin');
   Route::get('del/advert/{id}','API\V1\SystemController@delAdvert')->middleware('role:admin');
   Route::post('article','API\V1\SystemController@addArticle')->middleware('role:admin');
   Route::get('articles','API\V1\SystemController@getArticles')->middleware('role:admin');
   Route::post('commodity/picture','API\V1\CommodityController@addPicture')->middleware('role:admin');
   Route::get('commodity/pictures/{id}','API\V1\CommodityController@getPictures')->middleware('role:admin');
   Route::get('del/commodity/picture/{id}','API\V1\CommodityController@delPicture')->middleware('role:admin');
   Route::get('applies','API\V1\SystemController@listApply')->middleware('role:admin');
   Route::get('review/apply/{id}','API\V1\SystemController@reviewApply')->middleware('role:admin');
   Route::get('info','API\V1\StoreController@getInfo')->middleware('role:franchisee');
   Route::post('info','API\V1\StoreController@addStoreInfo')->middleware('role:franchisee');
   Route::post('add/user','API\V1\UserController@addUser')->middleware('role:admin');
   Route::get('del/app/{id}','API\V1\UserController@delApp')->middleware('role:admin');
   Route::post('modify/password/{id}','API\V1\UserController@modifyPassword')->middleware('role:admin');
   Route::get('apps','API\V1\UserController@listApps')->middleware('role:admin');
   Route::post('modify/app/{id}','API\V1\StoreController@modifyStoreApp')->middleware('role:admin');
   Route::get('output','API\V1\ExcelController@Output')->middleware('role:admin');
   Route::post('sys/config','API\V1\SystemController@modifySysConfig')->middleware('role:admin');
   Route::get('sys/config','API\V1\SystemController@getSysConfig')->middleware('role:admin');
   Route::post('user/score','API\V1\UserController@modifyScore')->middleware('role:admin');
    });
});
