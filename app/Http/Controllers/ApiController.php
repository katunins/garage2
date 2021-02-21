<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function checkAuth(Request $request)
    {
        if ($request->has('data'))
            return response()->json(User::wherePassword((string)$request->data)->first(), 200);
        else return response()->json($request->all(), 400);
    }
}
