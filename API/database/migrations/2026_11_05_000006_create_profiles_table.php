<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->text('address')->nullable();
            $table->string('bank_1_name', 100)->nullable();
            $table->string('bank_1_account', 50)->nullable();
            $table->string('bank_2_name', 100)->nullable();
            $table->string('bank_2_account', 50)->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
