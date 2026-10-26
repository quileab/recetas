<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('yield_quantity', 10, 3)->default(1);
            $table->string('yield_unit')->default('unidad');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('recipe_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->enum('type', ['ingredient', 'labor', 'packaging', 'direct_cost', 'overhead', 'preparation'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('items')->where('type', 'preparation')->update(['type' => 'ingredient']);

        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipe_id');
            $table->enum('type', ['ingredient', 'labor', 'packaging', 'direct_cost', 'overhead'])->change();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['yield_quantity', 'yield_unit']);
        });
    }
};
