<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/checkauth', [ApiController::class, 'checkAuth']);
Route::post('/getcalendar', [ApiController::class, 'getCalendar']);
Route::post('/getdetail', [ApiController::class, 'getDetailTask']);
Route::post('/taskaction', [ApiController::class, 'taskAction']);
