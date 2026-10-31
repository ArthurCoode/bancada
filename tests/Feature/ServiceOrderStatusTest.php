<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ServiceOrderStatus;
use App\Models\Customer;
use App\Models\Device;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create();
    }

    private function member(User $owner, Role $role): User
    {
        return User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => $role]);
    }

    private function orderFor(User $owner, ServiceOrderStatus $status = ServiceOrderStatus::Received): ServiceOrder
    {
        $customer = Customer::factory()->create(['tenant_id' => $owner->tenant_id]);
        $device = Device::factory()->forCustomer($customer)->create();

        return ServiceOrder::factory()->forDevice($device)->create(['status' => $status]);
    }

    private function change(User $user, ServiceOrder $order, string $to, ?string $note = null)
    {
        $data = ['status' => $to];

        if ($note !== null) {
            $data['note'] = $note;
        }

        return $this->actingAs($user)->patch("/ordens/{$order->id}/status", $data);
    }

    private function historyOf(ServiceOrder $order)
    {
        return ServiceOrderStatusHistory::withoutGlobalScopes()
            ->where('service_order_id', $order->id)
            ->orderBy('id')
            ->get();
    }

    // ---------- regras do fluxo (enum) ----------

    public function test_transition_rules(): void
    {
        $this->assertTrue(ServiceOrderStatus::Received->canTransitionTo(ServiceOrderStatus::InAnalysis));
        $this->assertFalse(ServiceOrderStatus::Received->canTransitionTo(ServiceOrderStatus::Delivered));
        $this->assertFalse(ServiceOrderStatus::Received->canTransitionTo(ServiceOrderStatus::Received));

        $this->assertTrue(ServiceOrderStatus::Delivered->isFinal());
        $this->assertTrue(ServiceOrderStatus::Cancelled->isFinal());
        $this->assertFalse(ServiceOrderStatus::InRepair->isFinal());
    }

    public function test_only_rejecting_and_cancelling_require_a_note(): void
    {
        $this->assertTrue(ServiceOrderStatus::Rejected->requiresNote());
        $this->assertTrue(ServiceOrderStatus::Cancelled->requiresNote());
        $this->assertFalse(ServiceOrderStatus::Approved->requiresNote());
    }

    // ---------- mudança de status ----------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->patch('/ordens/1/status', ['status' => 'in_analysis'])->assertRedirect(route('login'));
    }

    public function test_technician_can_change_status_and_it_is_recorded(): void
    {
        $owner = $this->owner();
        $technician = $this->member($owner, Role::Technician);
        $order = $this->orderFor($owner);

        $this->change($technician, $order, 'in_analysis', 'Iniciando o diagnóstico')
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(ServiceOrderStatus::InAnalysis, $order->fresh()->status);

        $entry = $this->historyOf($order)->last();

        $this->assertSame(ServiceOrderStatus::Received, $entry->from_status);
        $this->assertSame(ServiceOrderStatus::InAnalysis, $entry->to_status);
        $this->assertSame($technician->id, $entry->user_id);
        $this->assertSame('Iniciando o diagnóstico', $entry->note);
    }

    public function test_invalid_transition_is_rejected_and_nothing_changes(): void
    {
        $owner = $this->owner();
        $order = $this->orderFor($owner);

        $this->change($owner, $order, 'delivered')->assertSessionHasErrors('status');

        $this->assertSame(ServiceOrderStatus::Received, $order->fresh()->status);
        $this->assertCount(0, $this->historyOf($order));
    }

    public function test_unknown_status_is_rejected(): void
    {
        $owner = $this->owner();
        $order = $this->orderFor($owner);

        $this->change($owner, $order, 'banana')
            ->assertSessionHasErrors(['status' => 'Selecione um status válido.']);
    }

    public function test_final_orders_cannot_change_anymore(): void
    {
        $owner = $this->owner();
        $order = $this->orderFor($owner, ServiceOrderStatus::Delivered);

        $this->change($owner, $order, 'in_repair')->assertSessionHasErrors('status');

        $this->assertSame(ServiceOrderStatus::Delivered, $order->fresh()->status);
    }

    // ---------- permissões por papel ----------

    public function test_attendant_can_record_the_customer_answer_and_delivery(): void
    {
        $owner = $this->owner();
        $attendant = $this->member($owner, Role::Attendant);
        $order = $this->orderFor($owner, ServiceOrderStatus::AwaitingApproval);

        $this->change($attendant, $order, 'approved')->assertRedirect();

        $this->assertSame(ServiceOrderStatus::Approved, $order->fresh()->status);
    }

    public function test_attendant_cannot_do_the_technical_steps(): void
    {
        $owner = $this->owner();
        $attendant = $this->member($owner, Role::Attendant);
        $order = $this->orderFor($owner, ServiceOrderStatus::Approved);

        $this->change($attendant, $order, 'in_repair')->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Approved, $order->fresh()->status);
    }

    public function test_technician_cannot_deliver_the_device(): void
    {
        $owner = $this->owner();
        $technician = $this->member($owner, Role::Technician);
        $order = $this->orderFor($owner, ServiceOrderStatus::Ready);

        $this->change($technician, $order, 'delivered')->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Ready, $order->fresh()->status);
    }

    public function test_only_the_owner_can_cancel(): void
    {
        $owner = $this->owner();
        $order = $this->orderFor($owner);

        $this->change($this->member($owner, Role::Technician), $order, 'cancelled', 'Motivo')->assertForbidden();
        $this->change($this->member($owner, Role::Attendant), $order, 'cancelled', 'Motivo')->assertForbidden();
        $this->assertSame(ServiceOrderStatus::Received, $order->fresh()->status);

        $this->change($owner, $order, 'cancelled', 'Cliente desistiu')->assertRedirect();
        $this->assertSame(ServiceOrderStatus::Cancelled, $order->fresh()->status);
    }

    // ---------- observação obrigatória ----------

    public function test_rejecting_and_cancelling_require_a_note(): void
    {
        $owner = $this->owner();
        $awaiting = $this->orderFor($owner, ServiceOrderStatus::AwaitingApproval);
        $received = $this->orderFor($owner);

        $this->change($owner, $awaiting, 'rejected')->assertSessionHasErrors('note');
        $this->change($owner, $received, 'cancelled')->assertSessionHasErrors('note');

        $this->change($owner, $awaiting, 'rejected', 'Achou caro')->assertSessionDoesntHaveErrors();
        $this->assertSame(ServiceOrderStatus::Rejected, $awaiting->fresh()->status);
    }

    // ---------- isolamento ----------

    public function test_order_from_another_tenant_is_not_found(): void
    {
        $owner = $this->owner();
        $strangerOrder = $this->orderFor($this->owner());

        $this->change($owner, $strangerOrder, 'in_analysis')->assertNotFound();

        $this->assertSame(ServiceOrderStatus::Received, $strangerOrder->fresh()->status);
    }

    // ---------- tela ----------

    public function test_show_page_offers_only_the_actions_of_the_role(): void
    {
        $owner = $this->owner();
        $order = $this->orderFor($owner);

        $this->actingAs($owner)->get("/ordens/{$order->id}")
            ->assertSee('Em análise')
            ->assertSee('Cancelado');

        $this->actingAs($this->member($owner, Role::Technician))->get("/ordens/{$order->id}")
            ->assertSee('Em análise')
            ->assertDontSee('Cancelado');

        $this->actingAs($this->member($owner, Role::Attendant))->get("/ordens/{$order->id}")
            ->assertSee('Nenhuma ação disponível');
    }

    public function test_show_page_says_when_the_order_is_closed(): void
    {
        $owner = $this->owner();
        $order = $this->orderFor($owner, ServiceOrderStatus::Delivered);

        $this->actingAs($owner)->get("/ordens/{$order->id}")->assertSee('Esta OS está encerrada');
    }

    // ---------- jornada completa ----------

    public function test_full_journey_through_the_roles_builds_the_history(): void
    {
        $owner = $this->owner();
        $technician = $this->member($owner, Role::Technician);
        $attendant = $this->member($owner, Role::Attendant);
        $order = $this->orderFor($owner);

        $this->change($technician, $order, 'in_analysis');
        $this->change($technician, $order, 'awaiting_approval');
        $this->change($attendant, $order, 'approved');
        $this->change($technician, $order, 'in_repair');
        $this->change($technician, $order, 'ready');
        $this->change($attendant, $order, 'delivered');

        $this->assertSame(ServiceOrderStatus::Delivered, $order->fresh()->status);

        $history = $this->historyOf($order);

        $this->assertCount(6, $history);
        $this->assertSame(
            ['in_analysis', 'awaiting_approval', 'approved', 'in_repair', 'ready', 'delivered'],
            $history->map(fn ($entry) => $entry->to_status->value)->all(),
        );

        $this->change($owner, $order, 'in_repair')->assertSessionHasErrors('status');
    }

    public function test_customer_page_lists_the_service_orders(): void
    {
        $owner = $this->owner();
        $order = $this->orderFor($owner);

        $this->actingAs($owner)->get("/clientes/{$order->customer_id}")
            ->assertOk()
            ->assertSee('OS 0001')
            ->assertSee('Recebido');
    }
}