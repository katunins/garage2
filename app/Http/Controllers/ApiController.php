<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function checkAuth(Request $request)
    {
        if ($request->has('password'))
            return response()->json(User::where('password', $request->code)->first(), 200);
        else return response()->json(false, 400);
    }
}
