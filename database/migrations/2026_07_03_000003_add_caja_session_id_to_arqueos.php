<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('arqueos', function (Blueprint $table) {
            $table->unsignedBigInteger('caja_session_id')->nullable()->after('id');
            $table->foreign('caja_session_id')->references('id')->on('caja_sessions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('arqueos', function (Blueprint $table) {
            $table->dropForeign(['caja_session_id']);
            $table->dropColumn('caja_session_id');
        });
    }
};
