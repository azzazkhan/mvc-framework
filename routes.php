<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/', 'HomeController@index');
Route::get('/', function (Request $request) {
    return $request->input('foo');
});
