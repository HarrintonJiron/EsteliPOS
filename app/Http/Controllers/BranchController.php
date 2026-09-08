<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Warehouse;
use App\Services\ExecutiveAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private ExecutiveAnalyticsService $analytics) {}

    public function index(): View
    {
        return view('sucursales.index', [
            'scorecard' => $this->analytics->branchScorecard(),
        ]);
    }

    public function create(): View
    {
        return view('sucursales.create', $this->formData());
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $branch = Branch::query()->create($request->validated());

        return redirect()->route('sucursales.show', $branch)
            ->with('success', 'Sucursal creada correctamente.');
    }

    public function show(Branch $branch): View
    {
        $row = $this->analytics->branchScorecard()->first(
            fn (array $item): bool => $item['branch']->is($branch)
        );

        return view('sucursales.show', [
            'branch' => $branch->load(['warehouse', 'costCenter', 'employees']),
            'metrics' => $row ?? [
                'sales_month' => 0,
                'stock_value' => 0,
                'employees' => $branch->employees->count(),
                'share' => $branch->share_percent,
            ],
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('sucursales.edit', $this->formData($branch));
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($request->validated());

        return redirect()->route('sucursales.show', $branch)
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    private function formData(?Branch $branch = null): array
    {
        return [
            'branch' => $branch ?? new Branch(['is_active' => true, 'type' => 'sucursal']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'code', 'name']),
            'costCenters' => CostCenter::query()->orderBy('code')->get(['id', 'code', 'name']),
            'types' => Branch::TYPES,
        ];
    }
}
