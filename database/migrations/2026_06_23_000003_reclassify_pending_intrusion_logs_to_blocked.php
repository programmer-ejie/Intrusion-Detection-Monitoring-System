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
        DB::statement("UPDATE intrusion_logs SET status = NULL WHERE status = 'pending'");
        DB::statement("ALTER TABLE intrusion_logs MODIFY status ENUM('blocked', 'resolved') NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE intrusion_logs MODIFY status ENUM('pending', 'blocked', 'resolved') NULL DEFAULT NULL");
    }
};
