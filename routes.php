<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', 'HomeController@index');
Route::get('/', function () {
    return DB::table('users')->select(['name', 'email'])->get();
});
