<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('device_id')->constrained()->restrictOnDelete();
            // Se o membro sair da equipe, a OS permanece, só fica sem técnico/autor.
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('received');
            $table->text('reported_issue');
            $table->text('accessories')->nullable();
            $table->json('entry_checklist')->nullable();
            $table->text('device_password')->nullable(); // guarda o texto CIFRADO
            $table->date('due_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};