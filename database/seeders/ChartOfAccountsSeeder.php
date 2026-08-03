<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Catálogo de cuentas base para empresas nicaragüenses (comercial/POS).
     * Estructura: código => [nombre, tipo, naturaleza, postable, hijos => [...]]
     */
    public function run(): void
    {
        $tree = [
            '1' => ['Activo', 'asset_current', 'debit', false, [
                '1.1' => ['Activo Corriente', 'asset_current', 'debit', false, [
                    '1.1.01' => ['Caja General', 'asset_current', 'debit', true, []],
                    '1.1.02' => ['Banco Lafise', 'asset_current', 'debit', true, []],
                    '1.1.03' => ['Banco BAC', 'asset_current', 'debit', true, []],
                    '1.1.04' => ['Clientes', 'asset_current', 'debit', true, []],
                    '1.1.05' => ['Inventario de Mercancías', 'asset_current', 'debit', true, []],
                    '1.1.06' => ['IVA Crédito Fiscal', 'asset_current', 'debit', true, []],
                    '1.1.07' => ['Anticipo a Proveedores', 'asset_current', 'debit', true, []],
                ]],
                '1.2' => ['Activo No Corriente', 'asset_non_current', 'debit', false, [
                    '1.2.01' => ['Mobiliario y Equipo', 'asset_non_current', 'debit', true, []],
                    '1.2.02' => ['Equipo de Cómputo', 'asset_non_current', 'debit', true, []],
                    '1.2.03' => ['Vehículos', 'asset_non_current', 'debit', true, []],
                    '1.2.04' => ['Depreciación Acumulada', 'asset_non_current', 'debit', true, []],
                ]],
            ]],
            '2' => ['Pasivo', 'liability_current', 'credit', false, [
                '2.1' => ['Pasivo Corriente', 'liability_current', 'credit', false, [
                    '2.1.01' => ['Proveedores', 'liability_current', 'credit', true, []],
                    '2.1.02' => ['IVA por Pagar', 'liability_current', 'credit', true, []],
                    '2.1.03' => ['Retenciones por Pagar', 'liability_current', 'credit', true, []],
                    '2.1.04' => ['Sueldos y Salarios por Pagar', 'liability_current', 'credit', true, []],
                    '2.1.05' => ['Préstamos a Corto Plazo', 'liability_current', 'credit', true, []],
                ]],
                '2.2' => ['Pasivo Largo Plazo', 'liability_long_term', 'credit', false, [
                    '2.2.01' => ['Préstamos a Largo Plazo', 'liability_long_term', 'credit', true, []],
                ]],
            ]],
            '3' => ['Capital', 'equity', 'credit', false, [
                '3.1' => ['Capital Social', 'equity', 'credit', true, []],
                '3.2' => ['Utilidades Retenidas', 'equity', 'credit', true, []],
                '3.3' => ['Utilidad del Ejercicio', 'equity', 'credit', true, []],
            ]],
            '4' => ['Ingresos', 'revenue', 'credit', false, [
                '4.1' => ['Ventas', 'revenue', 'credit', true, []],
                '4.2' => ['Devoluciones sobre Ventas', 'revenue', 'debit', true, []],
                '4.3' => ['Descuentos sobre Ventas', 'revenue', 'debit', true, []],
            ]],
            '5' => ['Costos', 'cost_of_sales', 'debit', false, [
                '5.1' => ['Costo de Ventas', 'cost_of_sales', 'debit', true, []],
                '5.2' => ['Ajustes de Inventario (Faltantes)', 'cost_of_sales', 'debit', true, []],
            ]],
            '6' => ['Gastos', 'expense', 'debit', false, [
                '6.1' => ['Gastos Operativos', 'expense', 'debit', false, [
                    '6.1.01' => ['Sueldos y Salarios', 'expense', 'debit', true, []],
                    '6.1.02' => ['Alquiler', 'expense', 'debit', true, []],
                    '6.1.03' => ['Energía Eléctrica', 'expense', 'debit', true, []],
                    '6.1.04' => ['Agua Potable', 'expense', 'debit', true, []],
                    '6.1.05' => ['Combustible', 'expense', 'debit', true, []],
                    '6.1.99' => ['Gastos Operativos Taller', 'expense', 'debit', true, []],
                ]],
                '6.2' => ['Gastos Administrativos', 'expense', 'debit', false, [
                    '6.2.01' => ['Papelería y Útiles', 'expense', 'debit', true, []],
                    '6.2.02' => ['Depreciaciones', 'expense', 'debit', true, []],
                    '6.2.03' => ['Mantenimiento', 'expense', 'debit', true, []],
                ]],
                '6.3' => ['Gastos Financieros', 'expense', 'debit', false, [
                    '6.3.01' => ['Comisiones Bancarias', 'expense', 'debit', true, []],
                    '6.3.02' => ['Intereses Pagados', 'expense', 'debit', true, []],
                ]],
            ]],
            '7' => ['Otros Ingresos', 'other_income', 'credit', false, [
                '7.1' => ['Ingresos por Intereses', 'other_income', 'credit', true, []],
                '7.2' => ['Otros Ingresos Varios', 'other_income', 'credit', true, []],
            ]],
            '8' => ['Otros Gastos', 'other_expense', 'debit', false, [
                '8.1' => ['Pérdidas Varias', 'other_expense', 'debit', true, []],
            ]],
        ];

        foreach ($tree as $code => $node) {
            $this->createNode($code, $node, null);
        }
    }

    private function createNode(string $code, array $node, ?int $parentId): void
    {
        [$name, $type, $nature, $isPostable, $children] = $node;

        $account = Account::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'nature' => $nature,
                'parent_id' => $parentId,
                'is_postable' => $isPostable,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        foreach ($children as $childCode => $childNode) {
            $this->createNode($childCode, $childNode, $account->id);
        }
    }
}
