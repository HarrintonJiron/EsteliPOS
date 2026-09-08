<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('caja_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->timestamp('opened_at')->nullable();
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->foreign('opened_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('caja_sessions');
    }
};
