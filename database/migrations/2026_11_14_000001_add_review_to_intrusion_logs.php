<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intrusion_logs', function (Blueprint $table) {
            $table->string('actual_label', 10)->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('intrusion_logs', fn (Blueprint $table) => $table->dropColumn([
            'actual_label', 'review_notes', 'reviewed_by', 'reviewed_at',
        ]));
    }
};
