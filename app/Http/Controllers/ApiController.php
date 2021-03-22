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
            $notFinished = Tasks::where('master', $request->data['user_id'])
                ->where('status', '<>', 'finished')
                ->whereDate('end', '<', Carbon::parse($request->data['date']))
                ->count();

            return response()->json(['tasks' => $tasks, 'notfinished' => $notFinished], 200);
        } elseif ($request->has('type') && $request->type === 'filterData' && isset($request->filterData)) {
            $filter = [];
            foreach ($request->filterData as $item) {
                $filter[] = [$item['param'], $item['equality'], $item['value']];
            }
            // if ($request->filterData['deal'] ?? false) $filter[] = ['deal', 'like', '%' . $request->filterData['deal'] . '%'];
            // if ($request->filterData['master'] ?? false) $filter[] = ['master', '=', $request->filterData['master']];
            $tasks = Tasks::where($filter)->orderBy('start')->get();

            foreach ($tasks as $item) {
                $user = User::find($item->master);
                $item->mastername = $user->name;
                $item->masteravatar = $user->avatar ?? '';
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
}
