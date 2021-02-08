<?php

namespace App\Http\Controllers;

use App\Models\Tasks;
use App\Models\User;
use Illuminate\Support\Carbon;

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
        // $Date = isset($_GET['date']) !== false ? Carbon::createFromFormat('Y-m-d', $_GET['date']) : new Carbon;
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
            'finished' => 'Завершена',
        ] as $key => $item) {
            if (isset($_GET['status-' . $key]) !== false) {
                $statusFilter['status-' . $key] = $_GET['status-' . $key];
                session()->put('status-' . $key, $_GET['status-' . $key]);
            
            } else {
                if (session()->has('status-' . $key)) {
                    $statusFilter['status-' . $key] = session()->get('status-' . $key);
                } else {
                    $statusFilter['status-' . $key]='1';
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
        $endCalendarTime->hour = floor($workTimeEnd + $beforeAfter);
        $endCalendarTime->minute = $beforeAfter * 60;
        $endCalendarTime->second = 0;

        $filterDealName = $_GET['filterdealname'] ?? '';

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
            ->with('Tasks', self::getTask($startCalendarTime, $endCalendarTime, $gridInHour, $scale, $filterDealName, $statusFilter));
    }

    // получим задачи во временном периоде для календаря и расчитаем в них строки начала и строки конца для Grid
    static function getTask($startCalendarTime, $endCalendarTime, $gridInHour, $scale, $filterDealName, $statusFilter)
    {

        // $startCalendarTime, $endCalendarTime - время начала и конца линии календаря
        $gridStart = 2; //с этой ячейки начинается календарь
        $oneRow = $gridInHour / 60; //строк в одной минуте
        $minHeight = 26; //минимальная количество строк Grid в задаче в календаре из расчета на 30 px

        // dd ($statusFilter);
        $activeStatus = $statusFilter;
        foreach ($statusFilter as $key => $value) {
            if ($value == "1") {
                $activeStatus[]=explode('-', $key)[1];
            }
        }

        $tasks = Tasks::whereBetween('start', [$startCalendarTime, $endCalendarTime])
        ->whereBetween('end', [$startCalendarTime, $endCalendarTime])
        ->where('deal', 'like', '%' . $filterDealName . '%')
        ->whereIn('status', $activeStatus)
        ->get();

        foreach ($tasks as $item) {
            $taskStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $item->start);
            $taskEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $item->end);

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
}
