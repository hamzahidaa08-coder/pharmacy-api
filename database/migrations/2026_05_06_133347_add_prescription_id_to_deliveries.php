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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->change();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable(false)->change();
            $table->dropColumn('prescription_id');
        });
    }
};
