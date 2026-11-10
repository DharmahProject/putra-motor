<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasLogoData = Schema::hasColumn('profiles', 'logo_data');
        $hasLogoMime = Schema::hasColumn('profiles', 'logo_mime');
        Schema::table('profiles', function (Blueprint $table) use ($hasLogoData, $hasLogoMime) {
            if (!$hasLogoData) {
                $table->longBlob('logo_data')->nullable()->after('logo_path');
            }
            if (!$hasLogoMime) {
                $table->string('logo_mime', 100)->nullable()->after('logo_data');
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['logo_data', 'logo_mime'],
            static fn (string $column): bool => Schema::hasColumn('profiles', $column)
        ));
        Schema::table('profiles', function (Blueprint $table) use ($columns) {
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
