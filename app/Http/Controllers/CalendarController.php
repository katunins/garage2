<?php

namespace App\Http\Controllers;

use App\Models\StuckDeals;
use App\Models\Tasks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use function PHPUnit\Framework\isNull;

class CalendarController extends Controller
{
    // преобразуем дату в русский формат
    static function getRusDate($Date)
    {
        $monthes = array(
            1 => 'Января', 2 => 'Февраля', 3 => 'Марта', 4 => 'Апреля',
            5 => 'Мая', 6 => 'Июня', 7 => 'Июля', 8 => 'Августа',
            9 => 'Сентября', 10 => 'Октября', 11 => 'Ноября', 12 => 'Декабря'
        );
        $days = array(
            'Воскресенье', 'Понедельник', 'Вторник', 'Среда',
            'Четверг', 'Пятница', 'Суббота'
        );

        return ($Date->format('d') . ' ' . $monthes[$Date->format('n')] . ', ' . $days[$Date->format('w')]);
    }

    // загружает календарь
    public function initCalendar()
    {
        if (isset($_GET['date']) !== false) {

            $Date = Carbon::parse($_GET['date']);
            session()->put('calendarDate', $Date->toDateString());
        } else {
            if (session()->has('calendarDate')) {
                $Date = Carbon::parse(session()->get('calendarDate'));
            } else {
                $Date = new Carbon();
                session()->put('calendarDate', $Date->toDateString());
            }
        }

        foreach ([
            'temp' => 'Временный',
            'wait' => 'Ожидает выполнения',
            'repair' => 'В ремонте',
            'pause' => 'Задерживается',
            'finished' => 'Завершена',
        ] as $key => $item) {
            if (isset($_GET['status-' . $key]) !== false) {
                $statusFilter['status-' . $key] = $_GET['status-' . $key];
                session()->put('status-' . $key, $_GET['status-' . $key]);
            } else {
                if (session()->has('status-' . $key)) {
                    $statusFilter['status-' . $key] = session()->get('status-' . $key);
                } else {
                    $statusFilter['status-' . $key] = '1';
                    session()->put(('status-' . $key), $statusFilter['status-' . $key]);
                }
            }
        }

        if (isset($_GET['gridinhour']) !== false) {
            $gridInHour = $_GET['gridinhour'];
            session()->put('gridInHour', $gridInHour);
        } else {
            if (session()->has('gridInHour')) {
                $gridInHour = session()->get('gridInHour');
            } else {
                $gridInHour = 12;
                session()->put('gridInHour', $gridInHour);
            }
        }

        if (isset($_GET['calendarstyle']) !== false) {
            $calendarStyle = $_GET['calendarstyle'];
            session()->put('calendarStyle', $calendarStyle);
        } else {
            if (session()->has('calendarStyle')) {
                $calendarStyle = session()->get('calendarStyle');
            } else {
                $calendarStyle = 0;
                session()->put('calendarStyle', $calendarStyle);
            }
        }

        if (isset($_GET['calendardays']) !== false) {
            $calendarDays = $_GET['calendardays'];
            session()->put('calendarDays', $calendarDays);
        } else {
            if (session()->has('calendarDays')) {
                $calendarDays = session()->get('calendarDays');
            } else {
                $calendarDays = 1;
                session()->put('calendarDays', $calendarDays);
            }
        }


        $workTimeStart = 9; //вермя начала и конца
        $workTimeEnd = 18;
        $beforeAfter = 0.5; //дополнительные часы в сетки до и после

        $scale = 5; //масштаб пикселей для одной строки ячейки
        $gridRowCount = ($workTimeEnd - $workTimeStart + 1 + $beforeAfter * 2) * $gridInHour; //строк в одной линии для рабочего дня

        $startCalendarTime = clone $Date;
        $startCalendarTime->hour = floor($workTimeStart - $beforeAfter);
        $startCalendarTime->minute = $beforeAfter * 60;
        $startCalendarTime->second = 0;


        $endCalendarTime = clone $Date;
        if ($calendarDays == 7) $endCalendarTime->addWeek();

        $endCalendarTime->hour = floor($workTimeEnd + $beforeAfter);
        $endCalendarTime->minute = $beforeAfter * 60;
        $endCalendarTime->second = 0;

        $filterDealName = $_GET['filterdealname'] ?? null;

        return view('calendar')
            ->with('Users', User::where('type', 'master')->get())
            ->with('Date', $Date) //дата календаря
            ->with('today', new Carbon())
            ->with('rusDate', CalendarController::getRusDate($Date)) //дата на русском
            ->with('workTimeStart', $workTimeStart) //начало сетки календаря, час
            ->with('workTimeEnd', $workTimeEnd) //конец сетки календаря, час
            ->with('beforeAfter', $beforeAfter) //запас в начале и конце сетки, час
            ->with('gridInHour', $gridInHour) //количество строк Grid в одном часе
            ->with('gridRowCount', $gridRowCount) //всего строк в линейке календаря
            ->with('scale', $scale)
            ->with('filterDealName', $filterDealName)
            ->with('statusFilter', $statusFilter)
            ->with('calendarStyle', $calendarStyle)
            ->with('calendarDays', $calendarDays)
            ->with('Tasks', self::getTask($startCalendarTime, $endCalendarTime, $gridInHour, $scale, $filterDealName, $statusFilter));
    }

    // получим задачи во временном периоде для календаря и расчитаем в них строки начала и строки конца для Grid
    static function getTask($startCalendarTime, $endCalendarTime, $gridInHour, $scale, $filterDealName, $statusFilter)
    {
        // $startCalendarTime, $endCalendarTime - время начала и конца линии календаря
        $gridStart = 2; //с этой ячейки начинается календарь
        $oneRow = $gridInHour / 60; //строк в одной минуте
        $minHeight = 30; //минимальная количество строк Grid в задаче в календаре из расчета на 30 px

        // $filterDealNameExpression =if ($filterDealName) $filterDealName = ;
        $activeStatus = $statusFilter;
        foreach ($statusFilter as $key => $value) {
            if ($value == "1") {
                $activeStatus[] = explode('-', $key)[1];
            }
        }

        $tasks = Tasks::whereBetween('start', [$startCalendarTime, $endCalendarTime])
            ->whereBetween('end', [$startCalendarTime, $endCalendarTime])
            ->where(function ($query) use ($filterDealName) {
                if ($filterDealName) $query->where('deal', 'like', '%' . $filterDealName . '%');
            })
            ->whereIn('status', $activeStatus)
            ->get();

        foreach ($tasks as $item) {
            $taskStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $item->start);
            $taskEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $item->end);
            $startCalendarTime->setDateFrom($taskStartTime);

            $starlGridLine = (int)ceil($taskStartTime->diffInMinutes($startCalendarTime) * $oneRow + $gridStart);
            $endGridLine = (int)floor($taskEndTime->diffInMinutes($startCalendarTime) * $oneRow + $gridStart);
            $safeWidth = ($endGridLine - $starlGridLine) * $scale;
            if ($safeWidth < $minHeight) $endGridLine = $starlGridLine + round($minHeight / $scale); //если малый масштаб и высота < 30px, то делаем минимальную высоту

            //ячейка начала = разница в минутах между началом календаря и временем задачи
            $item->startGrid = $starlGridLine;
            $item->endGrid = $endGridLine;
        }

        return $tasks;
    }

    //создает кастомную задачу
    public function newCustomTask(Request $request)
    {
        // array:8 [▼
        //     "_token" => "GGwVnXwtDly9q8fee6m1viGWzuKnhaH47HTgROlE"
        //     "startdate" => "2021-03-10"
        //     "starttime" => "13:34"
        //     "generalparams" => "Фотокнига 20х20"
        //     "masterid" => array:2 [▼
        //         1 => "2"
        //         2 => "6"
        //     ]
        //     "producttime" => array:2 [▼
        //         1 => "10"
        //         2 => "20"
        //     ]
        //     "taskname" => array:2 [▼
        //         1 => "Поклейка"
        //         2 => "вторая задача"
        //     ]
        //     "miniparams" => array:2 [▼
        //         1 => "мини"
        //         2 => "мини2"
        //     ]

        TemplateController::$startTime = Carbon::parse($request->startdate . ' ' . $request->starttime);
        TemplateController::isHolidayInit();

        $taskIdBefore = null;

        foreach ($request->taskname as $key => $taskName) {

            TemplateController::getFreePlan($request->masterid[$key], $request->producttime[$key], TemplateController::$startTime, []);
            $endTime = clone TemplateController::$startTime;
            $endTime->addMinutes($request->producttime[$key]);

            $dealData = DealsController::getDeal($request->dealid);

            $newTask = new Tasks;
            $newTask->name = $taskName;
            $newTask->master = $request->masterid[$key];
            $newTask->time = $request->producttime[$key];
            $newTask->line = 0;
            $newTask->position = $key;
            $newTask->status = 'wait';
            $newTask->taskidbefore = $taskIdBefore;
            $newTask->start = TemplateController::$startTime;
            $newTask->end = $endTime;
            $newTask->buffer = $request->bufer[$key] ? $request->bufer[$key] : TemplateController::getStandartVars()['STANDART_BUFFER'];
            $newTask->generalinfo = $request->generalparams;
            $newTask->info = $request->miniparams[$key];
            $newTask->deal = $dealData['params']['deal'];
            $newTask->dealid = $request->dealid;
            $newTask->manager = $dealData['params']['manager'];
            if ($dealData['params']['managernote'] != "") $newTask->managernote = true;
            $newTask->save();
            $taskIdBefore = $newTask->id;
            $endTime->addMinutes($newTask->buffer);
            TemplateController::$startTime = clone $endTime;
        }
        return redirect('/calendar?calendarstyle=1&filterdealname=' . $dealData['params']['deal']);
    }

    // Возвращает просроченные задачи
    static function getOverTasks($whithBuffer = true)
    {
        $currentTime = Carbon::now();
        $resultTask = [];
        foreach (Tasks::whereNotIn('status', ['finished'])->get() as $taskItem) {
            $endTime = Carbon::parse($taskItem->end);
            if ($whithBuffer) $endTime->addMinutes($taskItem->buffer);
            if ($endTime->lessThan($currentTime)) $resultTask[] = $taskItem;
        }
        return collect($resultTask);
    }

    public function saveEditTask(Request $request)
    // array:8 [▼
    //     "_token" => "ENVz3Aq95KbD37hm3VtTJbojhmNsXaldNIgBpvdf"
    //     "taskid" => "737"
    //     "master" => "2"
    //     "taskname" => "Проверка фотографий"
    //     "generalinfo" => "Фотокниги 15х15 см (минибук), 10 разворотов"
    //     "info" => "Печать : Матовая, Формат : 15х15 см (минибук)"
    //     "start" => "2021-03-11 09:00:00"
    //     "time" => "2"

    // "id" => "941"
    // "_token" => "9UfOR6Q0kt5BH3WNTyeA0vgP1CmaSmRoRgmX4sJB"
    // "name" => "Завершить задачи битрикс"
    // "generalinfo" => "Завершить задачи битрикс"
    // "info" => "null"
    // "master" => "2"
    // "start" => "2021-03-11 09:00:00"
    // "time" => "60"
    // "bufer" => "10"


    {
        // $request->validate([
        //     'master' => 'required',
        //     'master' => 'required',
        //     'taskname' => 'required',
        //     'generalinfo' => 'required',
        //     'start' => 'required',
        //     'time' => 'required',
        // ], [
        //     // 'taskname.required' => 'Заполните название задачи',
        //     // 'masters.0.required' => 'Хотя бы один мастер должен быть указан',
        //     // 'producttime.required_without' => 'Должен быть заполнен хотя бы один параметр времени',
        //     // 'paramtime.required_without'=>'Должен быть заполнен хотя бы один параметр времени'
        // ]);
        // dd ($request->status);
        if ($request->deleteconfirm === 'on') {
            Tasks::find($request->id)->delete();
            return redirect()->back();
        }

        $startTime = Carbon::parse($request->start);
        $endTime = clone $startTime;
        $endTime->addMinutes($request->time);

        $task = $request->id === 'undefined' ? new Tasks : Tasks::find($request->id);
        $task->master = $request->master;
        $task->name = $request->name;
        $task->status = $request->status;
        $task->buffer = $request->bufer;
        $task->line = 1;

        $task->generalinfo = $request->generalinfo;
        $task->info = $request->info;

        $task->start = $startTime;
        $task->end = $endTime;
        $task->time = $request->time;
        $task->save();
        return redirect()->back();
    }

    static function getStuck()
    {
        // dd ((StuckDeals::where('taskId', 937)->first())?1:2);
        // "id" => 1
        // "taskId" => 937
        // "comment" => null
        // "type" => "pause"
        $result = [];
        foreach (StuckDeals::all() as $item) {

            $stuckTask = Tasks::find($item->taskId)->name;
            $dealId = Tasks::find($item->taskId)->dealid;
            if ($stuckTask) {
                $stuckDeal = is_null($dealId) ? 'Без сделки' : DealsController::getDeal(Tasks::find($item->taskId)->dealid)["params"]["deal"];
                $result[] = (object)[
                    'id' => $item->id,
                    'task' => $stuckTask,
                    'deal' => $stuckDeal,
                    'type' => $item->type
                ];
            }
        }

        return $result;
    }

    public function removestuck(Request $request)
    {
        StuckDeals::find($request->stuckid)->delete();
        return redirect()->back();
    }

    static function deadlineDeals()
    {
        foreach (Tasks::where('status', '!=', 'finished')->get()->groupBy('dealid') as $item) {
            $lastTask = $item->sortBy('end')->last();
            $lastTaskEnd = Carbon::parse($lastTask->end);
            $dealData = DealsController::getDeal($lastTask->dealid);
            // $deadLineString = $dealData['params']["Срок готовности"] ?? '2099.01.01|';
            $deadLine = Carbon::parse($dealData['params']["Срок готовности"]);
            $deadLine->setHour(10);
            if ($deadLine < $lastTaskEnd) echo '<b>' . $dealData['params']['deal'] . '</b>' . ', дата завершения - ' . ($dealData['params']['Срок готовности'] ?? 'Без срока') . '. Краяняя задача: ' . $lastTask->end . '<br>';
        }
    }

    static function newTaskStatus($id, $status)
    {

        $result = null;
        switch ($status) {
            case 'finished':
                $task = Tasks::find($id);
                if ($task) {
                    $task->status = $status;
                    $task->save();
                    $result = $task->id;

                    StuckDeals::where('taskId', $id)->delete();
                }
                break;

            case 'pause':
                $task = Tasks::find($id);
                if ($task) {
                    $task->status = $status;
                    $task->save();

                    if (StuckDeals::where('taskId', $id)->count() === 0) {
                        $stuck = new StuckDeals();
                        $stuck->taskId = (int)$id;
                        $stuck->type = $status;
                        $stuck->save();
                        $result = $stuck->id;
                    }
                }
                break;

            case 'wait':
                $task = Tasks::find($id);
                if ($task) {
                    $task->status = $status;
                    $task->save();
                }
                break;

            case 'empty':
                $task = Tasks::find($id);
                if ($task) {

                    $taskBefore = Tasks::find($task->taskidbefore);

                    $B24message = 'Сделка: ' . $task->deal . ', ' . 'Задача: ' . $task->name . '[br]';
                    if ($taskBefore) {
                        $B24message .= 'от ' . User::find($taskBefore->master)->name . ', задача ' . $taskBefore->name;

                        if (StuckDeals::where('taskId', $id)->count() === 0) {
                            $stuck = new StuckDeals();
                            $stuck->taskId = $taskBefore->id;
                            $stuck->type = $status;
                            $stuck->save();
                            $result = $stuck->id;
                        }
                    } else $B24message .= 'Странно, но предыдущей задачи не существует';

                    DealsController::bitrixAPI(array("TO" => [1, 8, 38], "MESSAGE" => 'У ' . User::find($task->master)->name . '  нет предыдущей поставки:[br]' . $B24message), 'im.notify');
                }

                break;

            default:
                break;
        }
        return $result;
    }
}
