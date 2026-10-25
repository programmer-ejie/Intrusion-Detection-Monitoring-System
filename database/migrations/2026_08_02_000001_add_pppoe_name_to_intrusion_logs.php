<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('intrusion_logs', 'pppoe_name')) {
            Schema::table('intrusion_logs', function (Blueprint $table) {
                $table->string('pppoe_name', 100)->nullable()->after('src_ip')->index();
            });
        }

        // Preserve the stable identities previously encoded in Pending Blocker.py.
        DB::table('intrusion_logs')->where('src_ip', '10.0.70.2')->update(['pppoe_name' => 'jimson']);
        DB::table('intrusion_logs')->where('src_ip', '10.0.70.5')->update(['pppoe_name' => 'jimson2']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('intrusion_logs', 'pppoe_name')) {
            Schema::table('intrusion_logs', function (Blueprint $table) {
                $table->dropIndex(['pppoe_name']);
                $table->dropColumn('pppoe_name');
            });
        }
    }
};
