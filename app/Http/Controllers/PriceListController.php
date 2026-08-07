<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function index(): View
    {
        $priceLists = PriceList::query()
            ->withCount('items')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('inventario.price-lists.index', compact('priceLists'));
    }

    public function create(): View
    {
        return view('inventario.price-lists.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:price_lists,code',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_default' => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            PriceList::query()->update(['is_default' => false]);
        }

        $list = PriceList::query()->create([
            ...$validated,
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]);

        return redirect()->route('inventario.price-lists.show', $list)->with('success', 'Lista de precios creada.');
    }

    public function show(PriceList $priceList): View
    {
        $items = PriceListItem::query()
            ->with(['product', 'unit'])
            ->where('price_list_id', $priceList->id)
            ->orderBy('product_id')
            ->paginate(30);

        $products = Product::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code', 'sale_price', 'base_unit_id']);
        $units = Unit::query()->where('is_active', true)->orderBy('name')->get();

        return view('inventario.price-lists.show', compact('priceList', 'items', 'products', 'units'));
    }

    public function edit(PriceList $priceList): View
    {
        return view('inventario.price-lists.edit', compact('priceList'));
    }

    public function update(Request $request, PriceList $priceList): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:price_lists,code,'.$priceList->id,
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            PriceList::query()->where('id', '!=', $priceList->id)->update(['is_default' => false]);
        }

        $priceList->update([
            ...$validated,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inventario.price-lists.show', $priceList)->with('success', 'Lista actualizada.');
    }

    public function storeItem(Request $request, PriceList $priceList): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_id' => 'nullable|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0.0001',
        ]);

        PriceListItem::query()->updateOrCreate(
            [
                'price_list_id' => $priceList->id,
                'product_id' => $validated['product_id'],
                'unit_id' => $validated['unit_id'] ?? null,
            ],
            [
                'unit_price' => $validated['unit_price'],
                'min_quantity' => $validated['min_quantity'] ?? 1,
            ]
        );

        return back()->with('success', 'Precio agregado a la lista.');
    }

    public function destroyItem(PriceList $priceList, PriceListItem $item): RedirectResponse
    {
        abort_unless($item->price_list_id === $priceList->id, 404);
        $item->delete();

        return back()->with('success', 'Precio eliminado de la lista.');
    }
}
