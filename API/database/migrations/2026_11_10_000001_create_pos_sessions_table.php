<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_sessions')) {
            return;
        }

        // Blueprint::longBlob() tidak tersedia di versi Laravel ini, jadi pakai SQL langsung
        // (sama dengan tabel yang dibuat DatabaseSessionHandler).
        DB::statement(
            'CREATE TABLE pos_sessions (
                id VARCHAR(128) NOT NULL,
                payload LONGBLOB NOT NULL,
                last_activity INT UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                KEY pos_sessions_last_activity_index (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
