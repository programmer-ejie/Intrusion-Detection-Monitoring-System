<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threat_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('pppoe_name', 100)->nullable()->index();
            $table->string('attack_type')->nullable()->index();
            $table->string('status', 20)->default('unresolved')->index();
            $table->unsignedInteger('event_count')->default(1);
            $table->timestamp('first_detected_at')->nullable();
            $table->timestamp('last_detected_at')->nullable()->index();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamp('firewall_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::table('intrusion_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('incident_id')->nullable()->after('id')->index();
        });

        // Preserve existing threat history. Status groups intentionally become
        // separate incidents so resolved rows cannot hide new unresolved rows.
        DB::table('intrusion_logs')
            ->where('risk_level', 'attack')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($log) {
                return implode('|', [
                    strtolower(trim((string) ($log->pppoe_name ?? ''))),
                    strtolower(trim((string) ($log->attack_type ?? 'unknown'))),
                    strtolower(trim((string) ($log->status ?? 'unresolved'))),
                ]);
            })
            ->each(function ($logs) {
                $first = $logs->first();
                $last = $logs->last();
                $status = strtolower((string) ($first->status ?? 'unresolved'));
                $status = in_array($status, ['blocked', 'resolved'], true) ? $status : 'unresolved';
                $now = now();

                $incidentId = DB::table('threat_incidents')->insertGetId([
                    'pppoe_name' => $first->pppoe_name ?: null,
                    'attack_type' => $first->attack_type ?: 'unknown',
                    'status' => $status,
                    'event_count' => $logs->count(),
                    'first_detected_at' => $first->created_at ?: $now,
                    'last_detected_at' => $last->created_at ?: $now,
                    'blocked_at' => $status === 'blocked' ? ($last->updated_at ?: $now) : null,
                    'restored_at' => $status === 'resolved' ? ($last->updated_at ?: $now) : null,
                    // Let the blocker verify that no subscriber-owned rule remains.
                    'firewall_synced_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('intrusion_logs')->whereIn('id', $logs->pluck('id'))->update([
                    'incident_id' => $incidentId,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('intrusion_logs', function (Blueprint $table) {
            $table->dropIndex(['incident_id']);
            $table->dropColumn('incident_id');
        });
        Schema::dropIfExists('threat_incidents');
    }
};
