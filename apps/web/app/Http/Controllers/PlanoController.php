<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PlanoController extends Controller
{
    public function index(): View
    {
        return view('planos.index');
    }
}
