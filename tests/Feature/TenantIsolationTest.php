<?php

namespace Tests\Feature;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('body');
            $table->timestamps();
        });

        // Cada usuário nasce em uma loja diferente.
        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();
    }

    public function test_user_only_sees_records_from_own_tenant(): void
    {
        TenantNote::create(['tenant_id' => $this->userA->tenant_id, 'body' => 'nota A']);
        TenantNote::create(['tenant_id' => $this->userB->tenant_id, 'body' => 'nota B']);

        $this->actingAs($this->userA);

        $this->assertSame(1, TenantNote::count());
        $this->assertSame('nota A', TenantNote::first()->body);
    }

    public function test_user_cannot_find_record_from_another_tenant_by_id(): void
    {
        $noteB = TenantNote::create(['tenant_id' => $this->userB->tenant_id, 'body' => 'nota B']);

        $this->actingAs($this->userA);

        $this->assertNull(TenantNote::find($noteB->id));
    }

    public function test_tenant_id_is_filled_from_authenticated_user_on_create(): void
    {
        $this->actingAs($this->userA);

        $note = TenantNote::create(['body' => 'nova nota']);

        $this->assertSame($this->userA->tenant_id, $note->tenant_id);
    }

    public function test_without_authenticated_user_nothing_is_returned(): void
    {
        TenantNote::create(['tenant_id' => $this->userA->tenant_id, 'body' => 'nota A']);

        $this->assertSame(0, TenantNote::count());
    }

    public function test_scope_can_be_removed_explicitly(): void
    {
        TenantNote::create(['tenant_id' => $this->userA->tenant_id, 'body' => 'nota A']);
        TenantNote::create(['tenant_id' => $this->userB->tenant_id, 'body' => 'nota B']);

        $this->assertSame(2, TenantNote::withoutGlobalScope(TenantScope::class)->count());
    }
}

class TenantNote extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_notes';

    protected $guarded = [];
}