<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();
        $digits = preg_replace('/\D+/', '', $term);

        $customers = Customer::query()
            ->when($term !== '', function ($query) use ($term, $digits) {
                // O orWhere vai dentro de um grupo, para nunca escapar do filtro de loja.
                $query->where(function ($group) use ($term, $digits) {
                    $group->where('name', 'like', "%{$term}%");

                    if ($digits !== '') {
                        $group->orWhere('phone', 'like', "%{$digits}%");
                    }
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', ['customers' => $customers]);
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        // O tenant_id é preenchido pela trait BelongsToTenant.
        $customer = Customer::create($request->validated());

        return redirect()->route('customers.show', $customer)->with('status', 'Cliente cadastrado.');
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', [
            'customer' => $customer,
            'devices' => $customer->devices()->orderBy('brand')->orderBy('model')->get(),
            'orders' => $customer->serviceOrders()->with('device')->orderByDesc('number')->get(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)->with('status', 'Cliente atualizado.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        if ($customer->devices()->exists()) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Não é possível remover: este cliente possui aparelhos cadastrados. Remova os aparelhos primeiro.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Cliente removido.');
    }
}