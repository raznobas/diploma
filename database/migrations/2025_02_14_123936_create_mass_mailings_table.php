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
        Schema::create('mass_mailings', function (Blueprint $table) {
            $table->id();
            $table->string('block');
            $table->json('selected_categories')->nullable();
            $table->text('message_text');
            $table->string('send_offset');

            $table->unsignedBigInteger('director_id')->nullable();
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_mailings');
    }
};
