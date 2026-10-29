<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceRequest;
use App\Models\Customer;
use App\Models\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();

        $devices = Device::query()
            ->with('customer')
            ->when($term !== '', function ($query) use ($term) {
                // Tudo dentro de um grupo, para os "or" nunca escaparem do filtro de loja.
                $query->where(function ($group) use ($term) {
                    $group->where('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('brand')
            ->orderBy('model')
            ->paginate(15)
            ->withQueryString();

        return view('devices.index', ['devices' => $devices]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $customerId = $request->integer('customer');

        if ($customerId === 0) {
            return redirect()->route('customers.index')
                ->with('status', 'Abra um cliente e clique em "Adicionar aparelho".');
        }

        // Busca pelo model: cliente de outra loja = 404.
        $customer = Customer::findOrFail($customerId);

        return view('devices.create', ['customer' => $customer]);
    }

    public function store(DeviceRequest $request): RedirectResponse
    {
        // O customer_id já foi validado dentro da loja; o tenant_id vem da trait.
        $device = Device::create($request->validated());

        return redirect()->route('customers.show', $device->customer_id)
            ->with('status', 'Aparelho cadastrado.');
    }

    public function edit(Device $device): View
    {
        return view('devices.edit', ['device' => $device->load('customer')]);
    }

    public function update(DeviceRequest $request, Device $device): RedirectResponse
    {
        $device->update($request->validated());

        return redirect()->route('customers.show', $device->customer_id)
            ->with('status', 'Aparelho atualizado.');
    }

    public function destroy(Device $device): RedirectResponse
    {
        Gate::authorize('delete', $device);

        if ($device->serviceOrders()->exists()) {
            return redirect()->route('devices.edit', $device)
                ->with('error', 'Não é possível remover: este aparelho possui ordens de serviço.');
        }

        $customerId = $device->customer_id;
        $device->delete();

        return redirect()->route('customers.show', $customerId)
            ->with('status', 'Aparelho removido.');
    }
}