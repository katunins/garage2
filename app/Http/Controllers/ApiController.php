<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function checkUserAuth(Request $request)
    {
        return response()->json(['aa' => $request->all()], 200);
    }
}
