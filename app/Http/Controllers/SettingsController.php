<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $activeGroup = active_group();
        return view('settings.index', compact('activeGroup'));
    }
}
