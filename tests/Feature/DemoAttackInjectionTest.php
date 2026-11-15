<?php

namespace Tests\Feature;

use App\Http\Controllers\ManageThreatController;
use App\Models\IntrusionLog;
use App\Models\ThreatIncident;
use App\Services\DemoAttackService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DemoAttackInjectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Http::preventStrayRequests();
        (require database_path('migrations/2025_12_14_101328_create_intrusion_logs_table.php'))->up();
        (require database_path('migrations/2025_12_19_000000_add_status_to_intrusion_logs_table.php'))->up();
        Schema::table('intrusion_logs', function (Blueprint $table) {
            $table->string('pppoe_name')->nullable();
        });
        (require database_path('migrations/2026_08_02_000002_create_threat_incidents_table.php'))->up();
    }

    public function test_all_fourteen_attacks_have_distinct_complete_samples(): void
    {
        $catalog = app(DemoAttackService::class)->catalog();
        $this->assertCount(14, $catalog);
        foreach ($catalog as $samples) {
            $this->assertCount(5, $samples);
            $this->assertCount(5, array_unique(array_map('json_encode', $samples)));
            foreach ($samples as $sample) {
                $this->assertCount(78, $sample);
                foreach ($sample as $value) {
                    $this->assertTrue(is_numeric($value) && is_finite((float) $value));
                }
            }
        }
    }

    public function test_real_prediction_is_saved_instead_of_selected_label(): void
    {
        Http::fake(['*' => Http::response(['risk_level' => 'attack', 'prob_attack' => 0.97, 'attack_type' => 'PORTSCAN'])]);
        $response = $this->inject();
        Http::assertSent(fn ($request) => $request['pppoe_name'] === 'jimson2'
            && $request['src_ip'] === '10.0.70.5'
            && $request['dst_ip'] === '10.0.70.249'
            && $request['flow_duration'] === app(DemoAttackService::class)->catalog()['DDoS'][0]['flow_duration']
            && !isset($request['attack_type']));
        $this->assertSame(route('admin.manage-threats'), $response->getTargetUrl());
        $this->assertDatabaseCount('intrusion_logs', 1);
        $this->assertDatabaseHas('threat_incidents', ['pppoe_name' => 'jimson2', 'attack_type' => 'PORTSCAN', 'status' => 'unresolved']);
        $this->assertDatabaseHas('intrusion_logs', ['attack_type' => 'PORTSCAN', 'prob_attack' => 0.97]);
        $this->assertSame('PORTSCAN', session('demo_prediction.result.attack_type'));
    }

    public function test_benign_result_does_not_create_a_threat(): void
    {
        Http::fake(['*' => Http::response(['risk_level' => 'benign', 'prob_attack' => 0.1, 'attack_type' => null])]);
        $this->inject();
        $this->assertDatabaseCount('threat_incidents', 0);
        $this->assertDatabaseHas('intrusion_logs', ['risk_level' => 'benign', 'incident_id' => null]);
    }

    public function test_remote_saved_log_is_reused_without_changing_block_status(): void
    {
        $incident = ThreatIncident::create([
            'pppoe_name' => 'jimson2', 'attack_type' => 'DDOS', 'status' => 'blocked',
            'event_count' => 1, 'first_detected_at' => now(), 'last_detected_at' => now(),
            'blocked_at' => now(), 'firewall_synced_at' => now(),
        ]);
        Http::fake(function ($request) use ($incident) {
            IntrusionLog::create(array_merge($request->data(), [
                'incident_id' => $incident->id, 'risk_level' => 'attack', 'prob_attack' => 0.99, 'attack_type' => 'DDOS',
            ]));
            return Http::response(['risk_level' => 'attack', 'prob_attack' => 0.99, 'attack_type' => 'DDOS']);
        });
        $this->inject();
        $this->assertDatabaseCount('intrusion_logs', 1);
        $this->assertDatabaseCount('threat_incidents', 1);
        $this->assertSame('blocked', $incident->fresh()->status);
        $this->assertNotNull($incident->fresh()->firewall_synced_at);
    }

    public function test_service_failure_does_not_insert_fake_predictions(): void
    {
        Http::fake(['*' => Http::response(['error' => 'unavailable'], 503)]);
        $this->inject();
        $this->assertDatabaseCount('intrusion_logs', 0);
        $this->assertDatabaseCount('threat_incidents', 0);
        $this->assertStringContainsString('503', session('error'));
        Http::assertSentCount(1);
    }

    public function test_invalid_prediction_is_rejected(): void
    {
        Http::fake(['*' => Http::response(['risk_level' => 'attack', 'prob_attack' => 2])]);
        $this->inject();
        $this->assertDatabaseCount('intrusion_logs', 0);
        $this->assertNotEmpty(session('error'));
    }

    public function test_render_timeout_returns_a_message_without_retrying_or_saving(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new \Illuminate\Http\Client\ConnectionException('Render timed out');
        });
        $response = $this->inject();
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('timed out', session('error'));
        $this->assertSame(1, $attempts);
        $this->assertDatabaseCount('intrusion_logs', 0);
        $this->assertDatabaseCount('threat_incidents', 0);
    }

    public function test_invalid_sample_cannot_be_submitted(): void
    {
        $this->expectException(ValidationException::class);
        $this->inject(['pppoe_name' => 'someone-else']);
    }

    private function inject(array $overrides = [])
    {
        $request = Request::create('/threats/demo', 'POST', array_merge([
            'pppoe_name' => 'jimson2', 'dst_ip' => '10.0.70.249',
            'attack_type' => 'DDoS', 'sample_index' => 0,
        ], $overrides));
        $request->setLaravelSession(app('session.store'));
        return app(ManageThreatController::class)->demo($request);
    }
}
