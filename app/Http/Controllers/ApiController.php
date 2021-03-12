<?php

namespace App\Http\Controllers;

use App\Models\StuckDeals;
use App\Models\Tasks;
use App\Models\User;
use Illuminate\Http\Request;

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
            return response()->json($tasks, 200);
        } else return response()->json($request->all(), 400);
    }

    public function getDetailTask(Request $request)
    {
        if ($request->has('data')) {
            $taskData = Tasks::find($request->data);
            if ($taskData) {
                $response = DealsController::getDeal($taskData->dealid);
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
            switch ($request->data['action']) {

                case 'finished':
                    $task = Tasks::find($request->data['taskId']);
                    $task->status = 'finished';
                    return response()->json($task->save(), 200);
                    break;

                case 'pause':
                    $task = Tasks::find($request->data['taskId']);
                    $task->status = 'pause';
                    
                    $stuckDeal = new StuckDeals();
                    $stuckDeal->taskId = (int)$request->data['taskId'];
                    $stuckDeal->type = 'pause';
                    $stuckDeal->save();


                    return response()->json($task->save(), 200);
                    break;

                case 'alert':
                    # code...
                    break;

                case 'empty':
                    $task = Tasks::find($request->data['taskId']);
                    $message = 'Сделка: ' . $task->deal . ', ' . 'Задача: ' . $task->name . '[br]';

                    $taskBefore = Tasks::find($task->taskidbefore);

                    $stuckDeal = new StuckDeals();
                    $stuckDeal->taskId = (int)$request->data['taskId'];
                    $stuckDeal->type = 'empty';
                    $stuckDeal->save();
                    
                    if ($taskBefore) {
                        $message .= 'от ' . User::find($taskBefore->master)->name . ', задача ' . $taskBefore->name;
                    } else $message .= 'Странно, но предыдущей задачи не существует';
                    return response()->json(DealsController::bitrixAPI(array("TO" => [1, 8, 38], "MESSAGE" => 'У ' . User::find($task->master)->name . '  нет предыдущей поставки:[br]' . $message), 'im.notify'), 200);

                    break;
            }
            return response()->json(false, 200);
        } else return response()->json($request->all(), 400);
    }

    public function feedback(Request $request)
    {
        if ($request->has('data')) {
            return response()->json($request->data, 200);
        } else return response()->json($request->all(), 400);
    }
}
