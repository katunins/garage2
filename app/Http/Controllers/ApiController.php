<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function checkAuth(Request $request)
    {
        if ($request->has('code'))
            return response()->json(User::where('code', $request->code), 200);
    }
}
