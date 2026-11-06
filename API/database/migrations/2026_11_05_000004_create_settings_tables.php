<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('Kasir')->after('password');
            $table->json('permissions')->nullable()->after('role');
            $table->boolean('active')->default(true)->after('permissions');
        });

        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 30)->unique();
            $table->string('name', 150);
            $table->string('phone', 30);
            $table->string('email', 150)->unique();
            $table->string('shift', 20);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions', 'active']);
        });
    }
};
