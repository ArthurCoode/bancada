<?php

namespace App\Services;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ServiceOrderService
{
    /**
     * Abre uma OS: reserva o número da loja, cria a OS e registra o 1º item do histórico.
     * Tudo em uma transação: se algo falhar, nada fica pela metade (nem o número é gasto).
     */
    public function open(User $user, array $data): ServiceOrder
    {
        return DB::transaction(function () use ($user, $data) {
            $order = new ServiceOrder(Arr::only($data, [
                'customer_id',
                'device_id',
                'technician_id',
                'reported_issue',
                'accessories',
                'device_password',
                'due_date',
                'internal_notes',
            ]));

            // Campos que o usuário NUNCA define: são nossos.
            $order->tenant_id = $user->tenant_id;
            $order->number = $this->nextNumber($user->tenant_id);
            $order->status = ServiceOrderStatus::Received;
            $order->created_by = $user->id;
            $order->entry_checklist = $this->buildChecklist($data['checklist'] ?? []);
            $order->save();

            $order->statusHistory()->create([
                'tenant_id' => $user->tenant_id,
                'from_status' => null,
                'to_status' => ServiceOrderStatus::Received,
                'user_id' => $user->id,
                'note' => 'OS aberta',
            ]);

            return $order;
        });
    }

    /**
     * Próximo número da loja. O UPDATE trava a linha da loja até o fim da transação,
     * então duas OS abertas ao mesmo tempo esperam a vez e recebem números diferentes.
     */
    public function nextNumber(int $tenantId): int
    {
        return DB::transaction(function () use ($tenantId) {
            Tenant::whereKey($tenantId)->increment('service_order_seq');

            return (int) Tenant::whereKey($tenantId)->value('service_order_seq');
        });
    }

    /** Monta o checklist só com os itens oficiais; o que faltar vira "não testado". */
    private function buildChecklist(array $input): array
    {
        $validStates = array_keys(ServiceOrder::CHECKLIST_STATES);
        $checklist = [];

        foreach (array_keys(ServiceOrder::CHECKLIST_ITEMS) as $item) {
            $state = $input[$item] ?? 'untested';
            $checklist[$item] = in_array($state, $validStates, true) ? $state : 'untested';
        }

        return $checklist;
    }
}