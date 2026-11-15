<?php

namespace Tests\Feature;

use App\Models\IntrusionLog;
use App\Models\UsersAccount;
use App\Services\ThreatReportService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThreatReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach ([
            '2025_12_14_101328_create_intrusion_logs_table.php',
            '2025_12_19_000000_add_status_to_intrusion_logs_table.php',
            '2026_08_02_000001_add_pppoe_name_to_intrusion_logs.php',
            '2026_11_14_000001_add_review_to_intrusion_logs.php',
            '2025_12_17_123456_create_users_account_table.php',
            '2026_10_29_000001_add_role_to_users_account_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    private function loginAs(string $role): UsersAccount
    {
        $user = UsersAccount::create(['fullname' => 'Reviewer', 'email' => $role.'@example.test', 'password' => 'unused', 'role' => $role]);
        $this->withSession(['user' => $user]);

        return $user;
    }

    private function event(string $prediction, ?string $actual = null, array $extra = []): IntrusionLog
    {
        $log = new IntrusionLog;
        $log->forceFill($extra + ['pppoe_name' => 'client-one', 'src_ip' => '10.0.0.2',
            'dst_ip' => '10.0.0.3', 'risk_level' => $prediction, 'prob_attack' => $prediction === 'attack' ? .95 : .1,
            'actual_label' => $actual, 'review_notes' => $actual ? 'Verified using packet capture.' : null])->save();

        return $log;
    }

    public function test_counts_include_blocked_history_and_only_reviewed_errors(): void
    {
        $this->event('attack', 'attack', ['status' => 'blocked']);
        $this->event('attack', 'benign');
        $this->event('benign', 'attack');
        $this->event('benign', 'benign');
        $this->event('attack');
        $this->event('attack', null, ['pppoe_name' => null, 'src_ip' => '10.0.0.9']);
        $service = app(ThreatReportService::class);
        $summary = $service->summary($service->query(Request::create('/')));
        $this->assertEquals(6, $summary['counts']->total);
        foreach (['tp', 'fp', 'fn', 'tn'] as $key) {
            $this->assertEquals(1, $summary['counts']->$key);
        }
        $this->assertEquals(2, $summary['counts']->unreviewed);
        $this->assertCount(2, $summary['clients']);
        $this->assertEquals('client-one', $summary['clients'][0]->client);
        $this->assertEquals(3, $summary['clients'][0]->attempts);
    }

    public function test_filters_apply_to_page_csv_and_pdf_evidence(): void
    {
        $this->loginAs('viewer');
        $this->event('benign', 'attack');
        $this->event('attack', null, ['pppoe_name' => 'other-client']);
        $this->event('attack', null, ['created_at' => now()->subDays(8)]);
        $url = '/threat-reports?window=7d&client=client-one';
        $this->get($url)->assertOk()->assertViewHas('total', 1)->assertSee('Export PDF')->assertSee('False negative')
            ->assertDontSee('other-client')->assertDontSee('Save review');
        $csv = $this->get('/threat-reports/export?window=7d&client=client-one')->assertOk()->streamedContent();
        $this->assertStringContainsString('False negative', $csv);
        $this->assertStringNotContainsString('other-client', $csv);
        $pdf = $this->get('/threat-reports/pdf?window=7d&client=client-one')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(10000, strlen($pdf));
    }

    public function test_admin_review_requires_evidence_and_does_not_change_prediction(): void
    {
        $user = $this->loginAs('admin');
        $log = $this->event('benign');
        $this->post('/threat-reports/'.$log->id.'/review', ['actual_label' => 'attack'])->assertSessionHasErrors('review_notes');
        $this->post('/threat-reports/'.$log->id.'/review', ['actual_label' => 'attack', 'review_notes' => 'Packet capture confirms attack.'])->assertRedirect();
        $log->refresh();
        $this->assertEquals('benign', $log->risk_level);
        $this->assertEquals($user->id, $log->reviewed_by);
        $this->assertNotNull($log->reviewed_at);
        $this->assertEquals('False negative', ThreatReportService::outcome($log));
        $this->get('/threat-reports')->assertOk()->assertSee('Save review');
    }

    public function test_viewers_cannot_review_and_guests_cannot_export(): void
    {
        $log = $this->event('attack');
        $this->get('/threat-reports/pdf')->assertRedirect('/login');
        $this->loginAs('viewer');
        $this->post('/threat-reports/'.$log->id.'/review', ['actual_label' => 'benign', 'review_notes' => 'Verified benign traffic.'])->assertForbidden();
        $this->assertNull($log->fresh()->actual_label);
    }

    public function test_empty_pdf_and_invalid_filters(): void
    {
        $this->loginAs('admin');
        $this->get('/threat-reports/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->getJson('/threat-reports/pdf?window=invalid')->assertUnprocessable();
    }
}
