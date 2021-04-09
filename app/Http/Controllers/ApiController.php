<?php

namespace App\Http\Controllers;

use App\Models\StuckDeals;
use App\Models\Tasks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ApiController extends Controller
{
    public function checkAuth(Request $request)
    {
        if ($request->has('data') && is_string($request->data))
            return response()->json(User::wherePassword((string)$request->data)->first(), 200);
        else return response()->json($request->all(), 400);
    }

    public function getCalendar(Request $request)
    {
        if ($request->has('data') && is_string($request->data['date']) && isset($request->data['user_id'])) {
            $tasks = Tasks::where('master', $request->data['user_id'])
                ->where(function ($query) {
                    global $request;
                    $query->whereDate('start', $request->data['date'])
                        ->orWhereDate('end', $request->data['date']);
                })
                ->orderBy('start')
                ->get();

            // пометим задачи, которые застряли
            foreach (StuckDeals::all() as $item) {
                $stuckTask = Tasks::find($item->taskId);
                foreach ($tasks->where('dealid', $stuckTask->dealid)->where('deal', $stuckTask->deal)->where('line', $stuckTask->line) as $el) {
                    $stuckTask->masterName = User::find($stuckTask->master)->name;
                    $el->stuck = $stuckTask;
                }
            }

            // добавим не закрытые задачи до этого периода
//            $notFinished = Tasks::where('master', $request->data['user_id'])
//                ->where('status', '<>', 'finished')
//                ->whereDate('end', '<', Carbon::parse($request->data['date']))
//                ->count();
            $notFinished = Tasks::where('master', $request->data['user_id'])
                ->where('status', '<>', 'finished')
                ->whereDate('end', '<', Carbon::parse($request->data['date']))->get();
            $notFinishedStuck = self::getStuckTasks($notFinished)->count();
            $notFinishedDeadline = $notFinished->count()-$notFinishedStuck;
            return response()->json(['tasks' => $tasks, 'notfinished' => ['deadline' => $notFinishedDeadline, 'stuck' => $notFinishedStuck]], 200);

        } elseif ($request->has('type') && $request->type === 'filterData' && isset($request->filterData)) {

            $filter = [];
            foreach ($request->filterData as $item) {
                if (isset($item['text'])) $filter[] = ['type' => 'where', 'condition' => [$item['text']['param'], $item['text']['equality'], $item['text']['value']]];
                if (isset($item['checkbox'])) {
                    foreach ($item['checkbox'] as $key => $checkbox) {

                        $checkFilter = [];
                        foreach ($checkbox as $el) {
                            $checkFilter[] = $el;
                        }
                        $filter[] = ['type' => 'whereIn', 'condition' => [$key, $checkFilter]];
                    }
                }
            }
            $tasks = Tasks::where('master', '<>', 9999); //уберем подрядчика
            $stuckIdArr = StuckDeals::pluck('taskid')->toArray();
            foreach ($filter as $item) {
                if ($item['type'] === 'where') $tasks = $tasks->where($item['condition'][0], $item['condition'][1], $item['condition'][2]);
                if ($item['type'] === 'whereIn') {
                    if ($item['condition'][0] === 'stuck') {
                        $stuckIds = [];
                        $tasks = $tasks->whereIn('id', $stuckIdArr);
                    } else $tasks = $tasks->whereIn($item['condition'][0], $item['condition'][1]);
                }
            }

            $tasks = $tasks->orderBy('start')->get();

            // окончательная подготовка - добавление параметров\
            $stuckDealIdArr = StuckDeals::pluck('dealid')->toArray();
            foreach ($tasks as $item) {
                $user = User::find($item->master);
                $item->mastername = $user->name;
                $item->masteravatar = $user->avatar ?? '';
                if (array_search($item->dealid, $stuckDealIdArr) !== false) {
                    $item->stuck = StuckDeals::where('dealid', $item->dealid)->first();
                    $stTask = Tasks::find($item->stuck->taskId);
                    $item->stuck->mastername = User::find($stTask->master)->name;
                    $item->stuck->task = $stTask;
                    // dd ($item->stuck);
                }
            }

            return response()->json(['tasks' => $tasks, 'filter' => $filter], 200);
        } else return response()->json($request->all(), 400);
    }

    public function getDetailTask(Request $request)
    {
        if ($request->has('data')) {
            $taskData = Tasks::find($request->data);
            if ($taskData) {
                $response = $taskData->dealid ? DealsController::getDeal($taskData->dealid) : null;
                $nextTask = Tasks::where('taskidbefore', $request->data)->first();
                if ($nextTask) $nextTask->masterName = User::find($nextTask->master);
                return response()->json(['deal' => $response, 'nextTask' => $nextTask], 200);
            } else return response()->json([], 200);
        } else return response()->json($request->all(), 400);
    }

    public function taskAction(Request $request)
        // {action: "completed", taskId: 937}
        // detailitem.js:42 {action: "pause", taskId: 937}
        // detailitem.js:42 {action: "alert", taskId: 937}
        // detailitem.js:42 {action: "empty", taskId: 937
    {
        if ($request->has('data') && isset($request->data['action']) && isset($request->data['taskId'])) {
            return response()->json(CalendarController::newTaskStatus($request->data['taskId'], $request->data['action'], $request), 200);
        } else return response()->json($request->all(), 400);
    }

    public function feedback(Request $request)
    {
        if ($request->has('data')) {
            return response()->json($request->data, 200);
        } else return response()->json($request->all(), 400);
    }

//возвращает обратно массив задач, если они есть в Stuck
    static function getStuckTasks($tasks)
    {
        $result = collect([]);
        foreach ($tasks as $task){
            foreach (StuckDeals::all() as $item) {
                $stuckTask = Tasks::find($item->taskId);
                if ($task->dealid === $stuckTask->dealid && $task->deal===$stuckTask->deal && $task->line === $stuckTask->line) {
                    $result->push($task);
                    continue 1;
                }
            }
        }
//        foreach (StuckDeals::all() as $item) {
//            $stuckTask = Tasks::find($item->taskId);
//            return $tasks->where('dealid', $stuckTask->dealid)->where('deal', $stuckTask->deal)->where('line', $stuckTask->line);
//        }
        return $result;
    }
}
