<?php

namespace App\Http\Controllers;

use App\Services\ModuleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(ModuleAccessService $modules): RedirectResponse
    {
        return redirect()->to($modules->defaultHomeUrl(auth()->user()));
    }

    public function unavailable(): View
    {
        return view('access.unavailable');
    }
}
