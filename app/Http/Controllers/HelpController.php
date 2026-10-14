<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HelpController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.help');
    }
}
