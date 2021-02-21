<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function checkAuth(Request $request)
    {
        return response()->json($request->all(), 200);
    }
}
