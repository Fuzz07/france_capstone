<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_record_activity_log()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        $log = ActivityLog::log('test_action', 'This is a test description');

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => 'admin',
            'action' => 'test_action',
            'description' => 'This is a test description',
        ]);
    }

    /** @test */
    public function admin_can_access_logs_page()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('logs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('settings.logs');
    }

    /** @test */
    public function standard_user_cannot_access_logs_page()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get(route('logs.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function guests_cannot_access_logs_page()
    {
        $response = $this->get(route('logs.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_can_filter_logs_by_role()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'user',
        ]);

        // Create some logs
        ActivityLog::log('login', 'Admin logged in', $admin);
        ActivityLog::log('login', 'Customer logged in', $customer);

        // Fetch logs with admin filter
        $response = $this->actingAs($admin)->get(route('logs.index', ['filter' => 'admin']));
        $response->assertStatus(200);
        $response->assertSee('Admin logged in');
        $response->assertDontSee('Customer logged in');

        // Fetch logs with customer filter
        $response = $this->actingAs($admin)->get(route('logs.index', ['filter' => 'customer']));
        $response->assertStatus(200);
        $response->assertSee('Customer logged in');
        $response->assertDontSee('Admin logged in');
    }
}
