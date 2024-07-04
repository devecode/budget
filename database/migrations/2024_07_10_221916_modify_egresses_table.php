<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('egresses', function (Blueprint $table) {
            $table->dropColumn('date_type');
            $table->boolean('is_fixed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('egresses', function (Blueprint $table) {
            $table->enum('date_type', ['fija', 'pendiente', 'programar'])->after('amount');
            $table->dropColumn('is_fixed');
        });
    }
};
