<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DealsController;
use App\Http\Controllers\TemplateController;
use App\Models\Products;
use App\Models\Tasks;
use App\Models\User;
use Illuminate\Support\Carbon;
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
    if ($_GET['time'] - time() > 1) return view('/'); //защитимся от перехода в браузере назад
    TemplateController::deleteTemplate($_GET['templateid']);
    return redirect('/board/' . $_GET['productid']);
});
Route::get('/movetemplate', function () {
    if ($_GET['time'] - time() > 1) return view('/'); //защитимся от перехода в браузере назад
    TemplateController::moveTemplate($_GET);
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
        dd(TemplateController::tasksFromDeal($dealArr));
    else
        return redirect()->back()->withErrors(['deal' => 'Возможно нет такой сделки']);
});

Route::get('/calendar', function () {
    
    if (isset($_GET['date']) !== false) $Date=Carbon::createFromFormat('Y-m-d', $_GET['date']);
    else $Date = new Carbon;

    $workTimeStart = 9; //вермя начала и конца
    $workTimeEnd = 18;
    $beforeAfter = 0.5; //дополнительные часы в сетки до и после
    $gridInHour = 60; //строк сетки в одном часе
    $scale = 5; //масштаб пикселей в одной ячейки
    $gridRowCount = ($workTimeEnd-$workTimeStart+1+$beforeAfter*2)*$gridInHour;//строк в одной линии для рабочего дня

    $startCalendarTime = clone $Date;
    $startCalendarTime->hour = floor($workTimeStart-$beforeAfter);
    $startCalendarTime->minute = $beforeAfter*60;
    $startCalendarTime->second =0;

    $endCalendarTime = clone $Date;
    $endCalendarTime->hour = floor($workTimeEnd+$beforeAfter);
    $endCalendarTime->minute = $beforeAfter*60;
    $endCalendarTime->second =0;


    return view('calendar')
    ->with('Users', User::where('type', 'master')->get())
    ->with('Date', $Date) //дата календаря
    ->with('rusDate', CalendarController::getRusDate($Date)) //дата на русском
    ->with('workTimeStart', $workTimeStart) //начало сетки календаря, час
    ->with('workTimeEnd', $workTimeEnd) //конец сетки календаря, час
    ->with('beforeAfter', $beforeAfter) //запас в начале и конце сетки, час
    ->with('gridInHour', $gridInHour) //количество строк Grid в одном часе
    ->with('gridRowCount', $gridRowCount) //всего строк в линейке календаря
    ->with('scale', $scale)
    ->with('Tasks', CalendarController::getTask($startCalendarTime, $endCalendarTime, $gridInHour, $scale));
});
