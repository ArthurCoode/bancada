<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\ServiceOrderStatus;
use App\Http\Requests\ChangeServiceOrderStatusRequest;
use App\Http\Requests\OpenServiceOrderRequest;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Services\ServiceOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceOrderController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();
        $status = ServiceOrderStatus::tryFrom($request->string('status')->toString());

        $orders = ServiceOrder::query()
            ->with(['customer', 'device', 'technician'])
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->when($term !== '', function ($query) use ($term) {
                // Só dígitos e curto (até 6): é o número da OS. Ex.: "15" -> OS 0015.
                // Um IMEI tem 15 dígitos, então números longos caem na busca normal.
                if (ctype_digit($term) && strlen($term) <= 6) {
                    $query->where('number', (int) $term);

                    return;
                }

                // Tudo dentro de um grupo, para os "or" nunca escaparem do filtro de loja.
                $query->where(function ($group) use ($term) {
                    $group->whereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('device', function ($device) use ($term) {
                            $device->where(function ($inner) use ($term) {
                                $inner->where('brand', 'like', "%{$term}%")
                                    ->orWhere('model', 'like', "%{$term}%")
                                    ->orWhere('serial_number', 'like', "%{$term}%");
                            });
                        });
                });
            })
            ->orderByDesc('number')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'statuses' => ServiceOrderStatus::cases(),
            'currentStatus' => $status,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $customerId = $request->integer('customer');

        if ($customerId === 0) {
            return redirect()->route('customers.index')
                ->with('status', 'Abra um cliente e clique em "Abrir OS" no aparelho.');
        }

        // Busca pelo model: cliente de outra loja = 404.
        $customer = Customer::findOrFail($customerId);

        $devices = $customer->devices()->orderBy('brand')->orderBy('model')->get();

        $technicians = $request->user()->tenant->users()
            ->whereIn('role', [Role::Owner->value, Role::Technician->value])
            ->orderBy('name')
            ->get();

        return view('orders.create', [
            'customer' => $customer,
            'deviceOptions' => $devices->mapWithKeys(fn ($device) => [
                $device->id => $device->label().($device->serial_number ? " ({$device->serial_number})" : ''),
            ])->all(),
            'selectedDevice' => $request->integer('device') ?: null,
            'technicianOptions' => $technicians->pluck('name', 'id')->all(),
            'checklistItems' => ServiceOrder::CHECKLIST_ITEMS,
            'checklistStates' => ServiceOrder::CHECKLIST_STATES,
        ]);
    }

    public function store(OpenServiceOrderRequest $request, ServiceOrderService $service): RedirectResponse
    {
        $order = $service->open($request->user(), $request->validated());

        return redirect()->route('orders.show', $order)
            ->with('status', "OS {$order->displayNumber()} aberta.");
    }

    public function show(Request $request, ServiceOrder $order): View
    {
        $order->load(['customer', 'device', 'technician', 'creator', 'statusHistory.user']);

        // Só os destinos que o fluxo permite E que o papel do usuário pode aplicar.
        $statusOptions = collect($order->status->allowedTransitions())
            ->filter(fn (ServiceOrderStatus $to) => $request->user()->can('changeStatus', [$order, $to]))
            ->mapWithKeys(fn (ServiceOrderStatus $to) => [$to->value => $to->label()])
            ->all();

        return view('orders.show', [
            'order' => $order,
            'statusOptions' => $statusOptions,
            'checklistItems' => ServiceOrder::CHECKLIST_ITEMS,
            'checklistStates' => ServiceOrder::CHECKLIST_STATES,
        ]);
    }

    public function changeStatus(
        ChangeServiceOrderStatusRequest $request,
        ServiceOrder $order,
        ServiceOrderService $service,
    ): RedirectResponse {
        $to = $request->enum('status', ServiceOrderStatus::class);

        $service->changeStatus($request->user(), $order, $to, $request->validated('note'));

        return redirect()->route('orders.show', $order)
            ->with('status', "Status alterado para {$to->label()}.");
    }
}