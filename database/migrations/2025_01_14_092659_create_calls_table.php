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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('entry_id');
            $table->string('phone_from');
            $table->string('phone_to');
            $table->dateTime('call_time');
            $table->integer('duration')->nullable();
            $table->enum('status', ['appeared', 'connected', 'onHold', 'disconnected', 'answered', 'missed'])->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('director_id')->nullable();
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
            $table->foreign('director_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
