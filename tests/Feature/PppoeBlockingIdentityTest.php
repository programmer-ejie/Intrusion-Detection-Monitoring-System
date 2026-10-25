<?php

namespace Tests\Feature;

use App\Http\Controllers\ManageThreatController;
use App\Models\IntrusionLog;
use App\Models\ThreatIncident;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PppoeBlockingIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('intrusion_logs');
        Schema::dropIfExists('threat_incidents');
        Schema::create('threat_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('pppoe_name')->nullable();
            $table->string('attack_type')->nullable();
            $table->string('status')->default('unresolved');
            $table->unsignedInteger('event_count')->default(1);
            $table->timestamp('first_detected_at')->nullable();
            $table->timestamp('last_detected_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamp('firewall_synced_at')->nullable();
            $table->timestamps();
        });
        Schema::create('intrusion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_id')->nullable();
            $table->string('src_ip')->nullable();
            $table->string('pppoe_name')->nullable();
            $table->string('dst_ip')->nullable();
            $table->string('attack_type')->nullable();
            $table->string('risk_level')->default('attack');
            $table->double('prob_attack')->default(1);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function test_block_and_restore_change_only_the_selected_incident(): void
    {
        $oldIncident = $this->incident('jimson2', 'port scan');
        $oldLog = $this->log($oldIncident, '10.0.70.5');
        $newIncident = $this->incident('jimson2', 'ddos');
        $newLog = $this->log($newIncident, '10.0.70.252');
        $otherIncident = $this->incident('jimson', 'port scan');

        $controller = app(ManageThreatController::class);
        $controller->block(Request::create('/block', 'POST'), $oldIncident->id);

        $this->assertSame('blocked', $oldIncident->fresh()->status);
        $this->assertSame('unresolved', $newIncident->fresh()->status);
        $this->assertSame('unresolved', $otherIncident->fresh()->status);
        $this->assertNull($oldLog->fresh()->status);
        $this->assertNull($newLog->fresh()->status);

        $controller->ignore(Request::create('/restore', 'POST'), $oldIncident->id);

        $this->assertSame('resolved', $oldIncident->fresh()->status);
        $this->assertNotNull($oldIncident->fresh()->restored_at);
        $this->assertNull($oldIncident->fresh()->firewall_synced_at);
        $this->assertSame('unresolved', $newIncident->fresh()->status);
    }

    public function test_resolved_history_and_new_unresolved_attack_coexist(): void
    {
        $resolved = $this->incident('jimson2', 'port scan', 'resolved');
        $this->log($resolved, '10.0.70.5');
        $newAttack = $this->incident('jimson2', 'port scan');
        $this->log($newAttack, '10.0.70.252');

        $this->assertDatabaseHas('threat_incidents', [
            'id' => $resolved->id,
            'status' => 'resolved',
        ]);
        $this->assertDatabaseHas('threat_incidents', [
            'id' => $newAttack->id,
            'status' => 'unresolved',
        ]);

        $controller = app(ManageThreatController::class);
        $resolvedView = $controller->index(Request::create('/manage-threats', 'GET', [
            'status' => 'resolved',
        ]));
        $unresolvedView = $controller->index(Request::create('/manage-threats', 'GET', [
            'status' => 'unresolved',
        ]));

        $this->assertSame([$resolved->id], $resolvedView->getData()['threats']->pluck('incident_id')->all());
        $this->assertSame([$newAttack->id], $unresolvedView->getData()['threats']->pluck('incident_id')->all());
    }

    private function incident(string $pppoe, string $attackType, string $status = 'unresolved'): ThreatIncident
    {
        return ThreatIncident::create([
            'pppoe_name' => $pppoe,
            'attack_type' => $attackType,
            'status' => $status,
            'event_count' => 1,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
            'restored_at' => $status === 'resolved' ? now() : null,
        ]);
    }

    private function log(ThreatIncident $incident, string $srcIp): IntrusionLog
    {
        return IntrusionLog::create([
            'incident_id' => $incident->id,
            'src_ip' => $srcIp,
            'pppoe_name' => $incident->pppoe_name,
            'dst_ip' => '198.51.100.20',
            'attack_type' => $incident->attack_type,
        ]);
    }
}
