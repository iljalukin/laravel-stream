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


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();
/*
Route::group(['middleware' => ['auth']], function(){

    Route::get('/', 'VideoController@index');

    Route::get('/uploader', 'VideoController@uploader')->name('uploader');

    Route::post('/upload', 'VideoController@store')->name('upload');

    Route::get('/videojobs', 'VideoController@videoJobs')->name('videoJobs');
    Route::get('/downloadjobs', 'DownloadController@downloadJobs')->name('downloadJobs');
    Route::get('/downloads', 'DownloadController@index');

});
*/
Route::group(['prefix' => 'api', 'middleware' => ['auth:api']], function(){
    Route::post('/download', 'DownloadController@store')->name('download');
    Route::get('/file/{filename}', 'VideoController@getFile')->name('getFile');


    Route::group(['prefix' => 'jobs'], function(){
        Route::get('/video', 'VideoController@jobs')->name('jobs');
        Route::get('/download', 'DownloadController@jobs')->name('jobs');
    });
    Route::post('/status', 'VideoController@status')->name('status');
    Route::post('/videos', 'VideoController@finished')->name('videos');

});