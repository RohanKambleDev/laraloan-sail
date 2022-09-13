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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->index('uuid');
            $table->foreignUuid('user_uuid')->references('uuid')->on('users')->onDelete('cascade');
            $table->double('amount', 12, 2);
            $table->integer('term');
            $table->foreignUuid('status_uuid')->references('uuid')->on('status')->onDelete('cascade');
            $table->char('frequency', 100)->default('weekly'); // All the loans will be assumed to have a “weekly” repayment frequency. (as per req. doc)
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
        Schema::dropIfExists('loans');
    }
};
