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
        Schema::create('collaboration_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('director_id');
            $table->string('manager_email');
            $table->string('director_email');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); // Статус заявки
            $table->timestamps();

            // Внешние ключи
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('director_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaboration_requests');
    }
};
