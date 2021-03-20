<?php
/**
 * Date: 2021/1/28
 * Time: 10:44
 */
use think\facade\Route;

//Route::group('v1',function (){
//    Route::get('member/index','/member/index');
//    Route::post('member/login','/member/login');
//})->prefix('v1');



Route::group('v1',function () {
    Route::group('member',function (){
        Route::any('index','/member/index');
        Route::post('login','/member/login');
        Route::any('mailRegister','/member/mailRegister');

    })->prefix('member');
//})->prefix('v1')->middleware(\app\api\middleware\Sign::class,23);
})->prefix('v1');
//->middleware(\app\api\middleware\Sign::class,23)