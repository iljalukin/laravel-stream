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


Auth::routes();

Route::group(['middleware' => ['auth']], function(){

    Route::get('/', 'VideoController@index');

    Route::get('/uploader', 'VideoController@uploader')->name('uploader');

    Route::post('/upload', 'VideoController@store')->name('upload');

    Route::get('/jobs', 'VideoController@jobs')->name('jobs');

});

Route::group(['prefix' => 'api'], function(){
    Route::post('/download', 'DownloadController@store')->name('download');
});