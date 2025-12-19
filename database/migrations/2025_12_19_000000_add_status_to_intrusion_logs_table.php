<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('intrusion_logs', function (Blueprint $table) {
            $table->enum('status', ['blocked', 'resolved'])->nullable()->default(null)->after('attack_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intrusion_logs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
