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
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->change();
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
        });

        Schema::table('lead_appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->change();
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->change();
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
        });

        Schema::table('client_status_history', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->change();
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->integer('client_id')->change();
        });

        Schema::table('lead_appointments', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->integer('client_id')->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->integer('client_id')->change();
        });

        Schema::table('client_status_history', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->integer('client_id')->change();
        });
    }
};
