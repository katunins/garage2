<?php

use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

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

Route::get('/board/{productID}', function ($productID) {
    return (TemplateController::getBoard($productID));
});

Route::get('/newtemplate', function(){
    return view ('newtemplate',[
        'line' => $_GET['line'],
        'position' => $_GET['position'],
        'productId' => $_GET['productid'],
        ]);
});
Route::get('/edittemplate', function(){
    return view ('newtemplate',[
        'line' => $_GET['line'],
        'position' => $_GET['position'],
        'productId' => $_GET['productid'],
        'template' => TemplateController::getTemplate($_GET['templateid']),
        ]);
});
Route::get('/deletetemplate', function(){
    TemplateController::deleteTemplate($_GET['templateid']);
    return redirect('/board/'.$_GET['productid']);
});
Route::get('/movetemplate', function(){
    TemplateController::moveTemplate($_GET);
    return redirect('/board/'.$_GET['productid']);
});


Route::post('/savetemplate', [TemplateController::class, 'saveTemplate']);