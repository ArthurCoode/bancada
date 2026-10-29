<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete: o banco recusa apagar um cliente que ainda tem aparelhos.
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('brand', 60);
            $table->string('model', 80);
            $table->string('serial_number', 40)->nullable();
            $table->string('color', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};