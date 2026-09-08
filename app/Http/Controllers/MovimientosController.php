<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;

class MovimientosController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min(35, (int) $request->query('per_page', 20)));
        $query = InventoryMovement::with('product', 'user')->latest();

        if ($productId = $request->query('producto')) {
            $query->where('product_id', $productId);
        }

        $movements = $query->paginate($perPage);

        $productName = null;
        if ($productId) {
            $product = Product::find($productId);
            $productName = $product ? $product->name : null;
        }

        return view('movimientos.index', compact('movements', 'productName'));
    }
}
