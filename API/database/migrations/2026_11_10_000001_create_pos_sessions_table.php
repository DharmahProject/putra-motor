<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_sessions')) {
            return;
        }

        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->string('id', 128)->primary();
            $table->longBlob('payload');
            $table->unsignedInteger('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
