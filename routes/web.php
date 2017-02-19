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

Auth::routes();

Route::get('/home', 'HomeController@index');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/concepts', [
        'as' => 'concept.index',
        'uses' => 'ConceptController@index',
    ]);

    Route::get('/concept/{concept}', [
        'as' => 'concept.show',
        'uses' => 'ConceptController@show',
    ]);

    Route::post('/concept/{concept}', [
        'as' => 'concept.update',
        'uses' => 'ConceptController@update',
    ]);
});

