<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scheduled_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->index('uuid');
            $table->foreignId('loan_id')->references('id')->on('loans')->cascadeOnDelete();
            $table->date('date');
            $table->double('amount', 12, 2);
            $table->double('amount_paid', 12, 2)->nullable();
            $table->foreignId('status_id')->references('id')->on('status')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduled_payments');
    }
};
