<?php
/**
 * Date: 2021/3/16
 * Time: 11:36
 */
use think\facade\Route;

Route::any('/mailres/:token','/mailres/register')
    ->pattern(['token' => '[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}'])
    ->name('coderegister');

Route::get('/mailres/index',function (){
    return view('mailres/index');
});

Route::post('/mailres/index','/mailres/index');

Route::post('/mailres/getcode','/mailres/getcode');

