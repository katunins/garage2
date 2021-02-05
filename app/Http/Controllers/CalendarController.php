<?php

namespace App\Http\Controllers;

use App\Models\Tasks;
use Illuminate\Http\Request;
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

    // получим задачи во временном периоде для календаря и расчитаем в них строки начала и строки конца для Grid
    static function getTask($startCalendarTime, $endCalendarTime, $gridInHour, $scale)
    {

        // $startCalendarTime, $endCalendarTime - время начала и конца линии календаря
        $gridStart = 2; //с этой ячейки начинается календарь
        $oneRow = $gridInHour / 60; //ширина одной ячейки

        $tasks = Tasks::whereBetween('start', [$startCalendarTime, $endCalendarTime])->whereBetween('end', [$startCalendarTime, $endCalendarTime])->get();

        foreach ($tasks as $item) {
            $taskStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $item->start);
            $taskEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $item->end);

            //ячейка начала = разница в минутах между началом календаря и временем задачи
            $item->startGrid = $taskStartTime->diffInMinutes($startCalendarTime)*$oneRow + 2;
            $item->endGrid = $taskEndTime->diffInMinutes($startCalendarTime)*$oneRow +2;
        }

        return $tasks;
    }
}
