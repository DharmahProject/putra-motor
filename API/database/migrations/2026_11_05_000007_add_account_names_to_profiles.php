<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('bank_1_account_name', 150)->nullable()->after('bank_1_name');
            $table->string('bank_2_account_name', 150)->nullable()->after('bank_2_name');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['bank_1_account_name', 'bank_2_account_name']);
        });
    }
};
