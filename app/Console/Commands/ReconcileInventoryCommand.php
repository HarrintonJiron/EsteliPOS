<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileInventoryCommand extends Command
{
    protected $signature = 'app:reconcile-inventory {--apply : Aplica las correcciones; sin esta opción solo muestra una vista previa}';

    protected $description = 'Compara el stock global con la suma por bodegas y permite reconciliarlo de forma auditable';

    public function handle(InventoryService $inventory): int
    {
        $preview = $inventory->reconcileAll(false);

        $this->table(
            ['ID', 'Código', 'Producto', 'Stock global', 'Total bodegas'],
            collect($preview['discrepancies'])->map(fn (array $item) => [
                $item['product']->id,
                $item['product']->code,
                $item['product']->name,
                $item['recorded'],
                $item['calculated'],
            ])->all(),
        );

        if ($preview['discrepancies'] === []) {
            $this->info('[OK] El inventario ya está reconciliado.');

            return self::SUCCESS;
        }

        if (! $this->option('apply')) {
            $this->warn('[VISTA PREVIA] No se modificaron datos. Use --apply para corregirlos.');

            return self::FAILURE;
        }

        $result = DB::transaction(function () use ($inventory) {
            $result = $inventory->reconcileAll(true);

            AuditLog::create([
                'action' => 'inventory.reconciled.command',
                'description' => "Reconciliación operativa: {$result['fixed']} productos corregidos",
                'new_values' => [
                    'fixed' => $result['fixed'],
                    'products' => collect($result['discrepancies'])->map(fn (array $item) => [
                        'product_id' => $item['product']->id,
                        'recorded' => $item['recorded'],
                        'corrected' => $item['calculated'],
                    ])->all(),
                ],
                'user_agent' => 'artisan app:reconcile-inventory',
            ]);

            return $result;
        });

        $this->info("[OK] Se corrigieron {$result['fixed']} productos y se registró la auditoría.");

        return self::SUCCESS;
    }
}
