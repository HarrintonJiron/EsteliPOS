<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ModuleAccessService;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(ModuleAccessService $moduleAccess)
    {
        $dashboardModules = $moduleAccess->accessibleSlugs(auth()->user());
        $data = $this->dashboardService->build($dashboardModules);

        return view('dashboard-general', array_merge($data, [
            'dashboardModules' => $dashboardModules,
        ]));
    }

    public function facturacion()
    {
        return view('dashboard');
    }
}
