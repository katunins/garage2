<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DealsController;
use App\Http\Controllers\TemplateController;
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
    // dd (DealsController::getDeal(31251));
    return view('welcome')
        ->with('overTasks', CalendarController::getOverTasks())
        ->with('Users', User::all())
        ->with('Stuck', CalendarController::getStuck());
});

// Route::get('/stuck', function () {
//     return view('stuck')
//         ->with('Stuck', CalendarController::getStuck());
// });

Route::get('/checkDeadline', function () {
    CalendarController::deadlineDeals();
});

Route::get('/templates', function () {
    // тут подтянем продукты из инфоблока
    // $products = Products::all();
    $products = TemplateController::getAllProducts();
    return view('templates')->with('products', $products);
});

Route::get('/masters', function () {
    return view('masters')->with('masters', User::where('type', 'master')->get());
});

Route::get('/board/{productID}', function ($productID) {
    return (TemplateController::getBoard($productID));
});

Route::get('/clonetemplate', function () {
    return (TemplateController::cloneTemplate($_GET));
});

Route::get('/newtemplate', function () {
    return view('newtemplate', [
        'line' => $_GET['line'],
        'position' => $_GET['position'],
        'productId' => $_GET['productid'],
        'allParams' => TemplateController::getAllProductParams($_GET['productid']),
        'Users' => User::all()
    ]);
});
Route::get('/edittemplate', function () {
    return view('newtemplate', [
        'line' => $_GET['line'],
        'position' => $_GET['position'],
        'productId' => $_GET['productid'],
        'template' => TemplateController::getTemplate($_GET['templateid']),
        'allParams' => TemplateController::getAllProductParams($_GET['productid']),
        'Users' => User::all()
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

// Route::get('/deal2tasks', function () {
//     $dealArr = DealsController::getDeal($_GET['id']);
//     if ($dealArr !== false) {
//         TemplateController::tasksFromDeal($dealArr);
//         return redirect('/calendar');
//     } else
//         return redirect('/')->withErrors(['deal' => 'такой сделки']);
// });

Route::post('/deal2tasks', [TemplateController::class, 'tasksFromDeal']);

Route::get('/deletealltasks', function () {
    if ($_GET['time'] - time() > 1) return redirect()->back(); //защитимся от перехода в браузере назад
    Tasks::whereIn('id', json_decode($_GET['taskstodelete']))->delete();
    return redirect('/calendar');
});

Route::get('/calendar', [CalendarController::class, 'initCalendar']);

Route::get('/rebuildtemplate/{productid}', function ($productid) {
    TemplateController::rebuildTemplate($productid);
    return redirect('/board/' . $productid);
});

Route::get('/repair', [TemplateController::class, 'repair']);
Route::get('/updateavatar', [User::class, 'updateAvatar']);

Route::get('/customdeal/{dealid}', function ($dealid) {
    return view('customdeal')->with('Users', User::all())->with('dealid', $dealid);
});

Route::post('/newcustomtask', [CalendarController::class, 'newCustomTask']);
Route::get('/edittask/{taskId}', function ($id) {
    $Task = Tasks::find($id);
    if ($Task) return view('edittask')
        ->with('Task', $Task)
        ->with('Users', User::all());
});
Route::post('/saveedittask', [CalendarController::class, 'saveEditTask']);
Route::get('/removestuck', [CalendarController::class, 'removeStuck']);

Route::post('/shiftTask', [CalendarController::class, 'shiftTask']);

Route::get('/startnewdeal', function () {
    return view('startnewdeal')->with('newDeals', DealsController::newDeals());
});
