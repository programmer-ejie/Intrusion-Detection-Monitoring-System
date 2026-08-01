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
        DB::table('intrusion_logs')
            ->where('dst_ip', '10.0.70.8')
            ->update(['dst_ip' => '10.0.70.9']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('intrusion_logs')
            ->where('dst_ip', '10.0.70.9')
            ->update(['dst_ip' => '10.0.70.8']);
    }
};
