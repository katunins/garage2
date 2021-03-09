<?php

namespace App\Http\Controllers;

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
                $nextTask = Tasks::where('taskidbefore',$request->data)->first();
                if ($nextTask) $nextTask->masterName = User::find($taskData->master);
                return response()->json(['deal'=>$response, 'nextTask'=>$nextTask], 200);
            } else return response()->json([], 200);
        } else return response()->json($request->all(), 400);
    }

    public function taskAction(Request $request)
    {
        if ($request->has('data')) {
            return response()->json($request->data, 200);
        } else return response()->json($request->all(), 400);
    }

    public function feedback(Request $request)
    {
        if ($request->has('data')) {
            return response()->json($request->data, 200);
        } else return response()->json($request->all(), 400);
    }
}
