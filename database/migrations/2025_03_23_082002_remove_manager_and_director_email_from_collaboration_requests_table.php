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
        Schema::table('collaboration_requests', function (Blueprint $table) {
            $table->dropColumn(['manager_email', 'director_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collaboration_requests', function (Blueprint $table) {
            $table->string('manager_email');
            $table->string('director_email');
        });
    }
};
