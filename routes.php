<?php

use Illuminate\Database\Database;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', 'HomeController@index');
Route::get('/', function () {
    return DB::table('lorem')->greet();
});
