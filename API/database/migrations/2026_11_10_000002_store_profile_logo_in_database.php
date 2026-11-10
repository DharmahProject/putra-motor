<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasLogoData = Schema::hasColumn('profiles', 'logo_data');
        $hasLogoMime = Schema::hasColumn('profiles', 'logo_mime');
        // Dipisah per kolom agar kompatibel dengan TiDB (lihat migration settings).
        // Blueprint::longBlob() tidak tersedia di versi Laravel ini, jadi pakai SQL langsung.
        if (!$hasLogoData) {
            DB::statement('ALTER TABLE profiles ADD COLUMN logo_data LONGBLOB NULL AFTER logo_path');
        }
        if (!$hasLogoMime) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->string('logo_mime', 100)->nullable()->after('logo_data');
            });
        }
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
