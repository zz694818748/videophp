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

Route::any('/test','/member/test');

Route::group('v1',function () {
    Route::group('member',function (){
        Route::group('',function (){
            Route::post('exitLogin','/member/exitLogin');
        })->middleware(\app\api\middleware\Sign::class,1);
        Route::any('index','/member/index');
        Route::post('pwdLogin','/member/pwdLogin');
        Route::post('mailRegister','/member/mailRegister');

    })->prefix('member');


})->prefix('v1')->middleware(\app\api\middleware\Sign::class);
//})->prefix('v1');

//->middleware(\app\api\middleware\Sign::class,23)