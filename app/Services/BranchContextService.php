<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CajaSession;

class BranchContextService
{
    public function resolve(?int $warehouseId, ?CajaSession $cashSession = null, ?int $userBranchId = null): ?Branch
    {
        $warehouseBranch = $warehouseId
            ? Branch::query()->where('is_active', true)->where('warehouse_id', $warehouseId)->first()
            : null;
        $sessionBranch = $cashSession?->branch;

        if ($sessionBranch && $warehouseBranch && ! $sessionBranch->is($warehouseBranch)) {
            throw new \RuntimeException(
                "La bodega seleccionada pertenece a {$warehouseBranch->name}, pero la caja está abierta en {$sessionBranch->name}."
            );
        }

        if ($sessionBranch && $sessionBranch->warehouse_id && $warehouseId && (int) $sessionBranch->warehouse_id !== $warehouseId) {
            throw new \RuntimeException('La bodega seleccionada no corresponde a la sucursal de la caja abierta.');
        }

        $resolved = $sessionBranch
            ?? $warehouseBranch
            ?? ($userBranchId ? Branch::query()->where('is_active', true)->find($userBranchId) : null);

        if ($userBranchId && $resolved && (int) $resolved->id !== $userBranchId) {
            throw new \RuntimeException('Tu usuario está asignado a otra sucursal y no puede operar con esta bodega o caja.');
        }

        return $resolved;
    }
}
