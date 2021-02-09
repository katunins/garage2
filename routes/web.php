<?php

use App\Http\Controllers\CalendarController;
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
        'Users' =>User::all()
    ]);
});

Route::get('/deletetemplate', function () {
    if ($_GET['time'] - time() > 1) return view('/'); //защитимся от перехода в браузере назад
    TemplateController::deleteTemplate($_GET['templateid']);
    return redirect('/board/' . $_GET['productid']);
});
Route::get('/movetemplate', function () {
    if ($_GET['time'] - time() > 1) return view('/'); //защитимся от перехода в браузере назад
    TemplateController::moveTemplate($_GET);
    return redirect('/board/' . $_GET['productid']);
});
Route::get('/copytemplate', function () {
    if ($_GET['time'] - time() > 1) return view('/'); //защитимся от перехода в браузере назад
    TemplateController::copyTemplate($_GET);
    return redirect('/board/' . $_GET['productid']);
});



Route::get('/moveline', function () {
    if ($_GET['time'] - time() > 1) return view('/'); //защитимся от перехода в браузере назад
    TemplateController::moveLine($_GET);
    return redirect('/board/' . $_GET['productid']);
});


Route::post('/savetemplate', [TemplateController::class, 'saveTemplate']);


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

Route::get('/deal2tasks', function () {

    $dealArr = DealsController::getDeal($_GET['id']);
    if ($dealArr !== false)
        if (TemplateController::tasksFromDeal($dealArr)) return redirect('/calendar');
        else
            return redirect()->back()->withErrors(['deal' => 'Возможно нет такой сделки']);
});
Route::get('/deletealltasks', function () {
    if ($_GET['time'] - time() > 1 || isset($_GET['confirm']) === false) return redirect()->back(); //защитимся от перехода в браузере назад
    Tasks::whereIn('id', json_decode($_GET['taskstodelete']))->delete();
    return redirect('/calendar');
});

Route::get('/calendar', [CalendarController::class, 'initCalendar']);


// Функционал
// менять мастера