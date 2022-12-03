<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Restream_Controller;

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

Route::get('/auth', [Restream_Controller::class, 'authStream']);
Route::post('/restreaming', [Restream_Controller::class, 'getRestreaming']);