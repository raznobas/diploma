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
        Schema::table('lead_appointments', function (Blueprint $table) {
            $table->dropColumn('sale_date');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->string('subscription_duration')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_appointments', function (Blueprint $table) {
            $table->date('sale_date')->nullable();
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subscription_duration', 5, 2)->nullable()->change();
        });
    }
};
