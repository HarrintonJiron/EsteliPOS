<?php

namespace App\Http\Controllers;

use App\Services\HelpCenterService;
use Illuminate\View\View;

class HelpCenterController extends Controller
{
    public function __invoke(HelpCenterService $helpCenter): View
    {
        return view('help.index', $helpCenter->content());
    }
}
