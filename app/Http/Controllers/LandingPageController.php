<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('landing.index', [
            'content' => config('landing'),
        ]);
    }
}
