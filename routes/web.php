<?php

use App\Http\Controllers\DealsController;
use App\Http\Controllers\TemplateController;
use App\Models\Products;
use App\Models\Tasks;
use App\Models\User;
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

Route::get('/templates', function () {
    return view('templates')->with('products', Products::all());
});

Route::get('/masters', function () {
    return view('masters')->with('masters', User::where('type', 'master')->get());
});

Route::get('/board/{productID}', function ($productID) {
    return (TemplateController::getBoard($productID));
});

Route::get('/newtemplate', function () {
    return view('newtemplate', [
        'line' => $_GET['line'],
        'position' => $_GET['position'],
        'productId' => $_GET['productid'],
    ]);
});
Route::get('/edittemplate', function () {
    return view('newtemplate', [
        'line' => $_GET['line'],
        'position' => $_GET['position'],
        'productId' => $_GET['productid'],
        'template' => TemplateController::getTemplate($_GET['templateid']),
    ]);
});

Route::get('/deletetemplate', function () {
    TemplateController::deleteTemplate($_GET['templateid']);
    return redirect('/board/' . $_GET['productid']);
});
Route::get('/movetemplate', function () {
    TemplateController::moveTemplate($_GET);
    return redirect('/board/' . $_GET['productid']);
});

Route::get('/moveline', function () {
    TemplateController::moveLine($_GET);
    return redirect('/board/' . $_GET['productid']);
});


Route::post('/savetemplate', [TemplateController::class, 'saveTemplate']);
Route::get('/deal2tasks', function () {
    $dealArr = DealsController::getDeal($_GET['id']);
    if ($dealArr !== false)
        dd(TemplateController::tasksFromDeal($dealArr));
    else
        return redirect()->back()->withErrors(['deal'=>'Возможно нет такой сделки']);
});

Route::get('/masteredit', function () {
    User::masterEdit($_GET);
    return redirect('/masters')->with('masters', User::where('type', 'master')->get());
});

Route::get('/tasksboard', function () {
    return view('tasklist')->with('tasks', Tasks::all())->with('users', User::where('type', 'master')->get());
});

Route::get('master/{id}', function ($id) {
    return view('tasklist')->with('tasks', Tasks::where('master', $id)->get());
});