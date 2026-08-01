<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE intrusion_logs
            SET
                created_at = COALESCE(created_at, updated_at, NOW()),
                updated_at = COALESCE(updated_at, created_at, NOW())
            WHERE created_at IS NULL OR updated_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left as-is. Timestamp backfills are not safely reversible.
    }
};
