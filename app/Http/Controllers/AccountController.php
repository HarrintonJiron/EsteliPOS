<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::with('parent')->orderBy('code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $accounts = $query->get();

        return view('contabilidad.cuentas.index', [
            'accounts' => $accounts,
            'types' => Account::TYPES,
        ]);
    }

    public function create()
    {
        return view('contabilidad.cuentas.create', [
            'types' => Account::TYPES,
            'parents' => Account::orderBy('code')->get(),
        ]);
    }

    public function store(AccountRequest $request)
    {
        $data = $request->validated();
        $data['nature'] = $data['nature'] ?? Account::NATURE_BY_TYPE[$data['type']];
        $data['is_postable'] = $request->boolean('is_postable', true);
        $data['is_active'] = $request->boolean('is_active', true);

        Account::create($data);

        return redirect()->route('contabilidad.cuentas.index')->with('success', 'Cuenta creada correctamente.');
    }

    public function edit(Account $account)
    {
        return view('contabilidad.cuentas.edit', [
            'account' => $account,
            'types' => Account::TYPES,
            'parents' => Account::where('id', '!=', $account->id)->orderBy('code')->get(),
        ]);
    }

    public function update(AccountRequest $request, Account $account)
    {
        $data = $request->validated();
        $data['is_postable'] = $request->boolean('is_postable', true);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($account->parent_id && (int) ($data['parent_id'] ?? 0) === $account->id) {
            return back()->withErrors(['parent_id' => 'Una cuenta no puede ser su propia cuenta padre.']);
        }

        $account->update($data);

        return redirect()->route('contabilidad.cuentas.index')->with('success', 'Cuenta actualizada correctamente.');
    }

    public function destroy(Account $account)
    {
        if ($account->is_system) {
            return back()->with('error', 'No se puede eliminar una cuenta del sistema.');
        }

        if ($account->children()->exists()) {
            return back()->with('error', 'No se puede eliminar una cuenta que tiene subcuentas.');
        }

        $account->delete();

        return redirect()->route('contabilidad.cuentas.index')->with('success', 'Cuenta eliminada correctamente.');
    }
}
