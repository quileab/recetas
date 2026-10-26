<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['ingredient', 'labor', 'packaging', 'direct_cost', 'overhead']);
            $table->string('category')->nullable();
            $table->string('name');
            $table->string('purchase_unit')->nullable(); // e.g. Kg, Mes, Hora
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->string('recipe_unit')->nullable(); // e.g. Gr, Minuto
            $table->decimal('conversion_factor', 10, 4)->default(1);
            $table->enum('overhead_type', ['percentage', 'fixed'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
