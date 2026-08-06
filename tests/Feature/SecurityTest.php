<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guests_are_redirected_to_login_from_protected_routes()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/products');
        $response->assertRedirect('/login');

        $response = $this->get('/pos');
        $response->assertRedirect('/login');

        $response = $this->get('/reports');
        $response->assertRedirect('/login');

        $response = $this->get('/settings');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function standard_users_are_forbidden_from_accessing_management_routes()
    {
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer_test_' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $this->actingAs($customer);

        $this->get('/dashboard')->assertStatus(403);
        $this->get('/products')->assertStatus(403);
        $this->get('/pos')->assertStatus(403);
        $this->get('/inquiries')->assertStatus(403);
        $this->get('/reports')->assertStatus(403);
        $this->get('/settings')->assertStatus(403);
        $this->get('/users')->assertStatus(403);
    }

    /** @test */
    public function admin_users_can_access_management_routes()
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin_test_' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $this->get('/dashboard')->assertStatus(200);
        $this->get('/products')->assertStatus(200);
        $this->get('/pos')->assertStatus(200);
        $this->get('/inquiries')->assertStatus(200);
        $this->get('/reports')->assertStatus(200);
        $this->get('/settings')->assertStatus(200);
        $this->get('/users')->assertStatus(200);
    }

    /** @test */
    public function response_includes_essential_security_headers()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /** @test */
    public function social_login_rejects_unverified_credentials()
    {
        $response = $this->postJson('/social-login', [
            'email' => 'fakeadmin@merasstore.com',
            'name' => 'Fake Admin',
            'provider' => 'google',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function social_login_returns_json_for_raw_json_payload()
    {
        $response = $this->post('/social-login', [
            'provider' => 'google',
            'credential' => 'invalid_token',
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertHeader('Content-Type', 'application/json');
        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_users_are_redirected_by_role_from_guest_routes()
    {
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer_test_' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $this->actingAs($customer);
        $this->get('/login')->assertRedirect('/');
        $this->get('/register')->assertRedirect('/');

        $admin = User::create([
            'name' => 'Test Admin2',
            'email' => 'admin_test2_' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/register')->assertRedirect('/dashboard');
    }
}
