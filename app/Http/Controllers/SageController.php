<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SageController extends Controller
{
    public function getComplete($type)
    {
        return view('sage.complete', compact('type'));
    }
}
