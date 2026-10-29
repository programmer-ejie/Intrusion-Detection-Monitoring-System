<?php

namespace Tests\Feature;

use App\Models\UsersAccount;
use Database\Seeders\ViewOnlyUserSeeder;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ViewOnlyAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2025_12_17_123456_create_users_account_table.php'))->up();
        (require database_path('migrations/2026_10_29_000001_add_role_to_users_account_table.php'))->up();
        $this->seed(ViewOnlyUserSeeder::class);
    }

    public function test_requested_credentials_can_log_in(): void
    {
        $this->post('/login', ['email' => 'user@idms.com', 'password' => 'user'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('user', fn ($user) => $user->role === 'viewer');
    }

    public function test_viewer_cannot_submit_any_threat_changes(): void
    {
        $this->withSession(['user' => UsersAccount::first()]);

        foreach (['/threats/demo', '/threats/1/block', '/threats/1/ignore', '/threats/bulk-action'] as $url) {
            $this->post($url)->assertForbidden();
        }
    }

    public function test_permissions_are_reloaded_and_admin_can_submit(): void
    {
        Route::post('/permission-test', fn () => response('allowed'))->middleware(['web', 'check.session']);
        $user = UsersAccount::first();
        $user->update(['role' => 'admin']);
        $this->withSession(['user' => $user])->post('/permission-test')->assertOk();
        $user->update(['role' => 'viewer']);
        $this->post('/permission-test')->assertForbidden();
    }

    public function test_viewer_can_read_and_mutating_controls_are_absent(): void
    {
        Route::get('/permission-test', fn () => response('allowed'))->middleware(['web', 'check.session']);
        $user = UsersAccount::first();
        $this->withSession(['user' => $user])->get('/permission-test')->assertOk();

        $threats = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $data = ['threats' => $threats, 'hasNotes' => false, 'hasPppoeName' => true];
        $this->view('admin.manage-threats', $data)
            ->assertDontSee('seedDemoModal')
            ->assertDontSee('<th>Actions</th>', false);

        $user->role = 'admin';
        session(['user' => $user]);
        $this->view('admin.manage-threats', $data)->assertSee('seedDemoModal');
    }
}
