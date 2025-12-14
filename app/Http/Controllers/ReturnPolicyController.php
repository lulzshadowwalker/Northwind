<?php

namespace App\Http\Controllers;

class ReturnPolicyController extends Controller
{
    public function index()
    {
        return view('return-policy.index');
    }
}
