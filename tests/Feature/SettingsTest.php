<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_settings_index_page()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_settings@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Settings');
        $response->assertSee('Seed Demo Products');
    }

    /** @test */
    public function non_admin_cannot_access_settings_index_page()
    {
        $customer = User::create([
            'name' => 'Regular Customer',
            'email' => 'customer_settings@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($customer)->get(route('settings.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_is_redirected_to_login_from_settings_index_page()
    {
        $response = $this->get(route('settings.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_seed_demo_products()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_seed@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Prior to seeding, there are 0 products in the database
        $this->assertEquals(0, Product::count());

        $response = $this->actingAs($admin)->post(route('settings.action'), [
            'action' => 'seed_products',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('notice', 'Demo inventory loaded and sales history reset.');

        // Total products count must be 73 (28 School Supplies, 27 Fabric, 18 General Merchandise)
        $this->assertEquals(73, Product::count());

        // Assert that new SKUs exist in the database with their respective details
        $this->assertDatabaseHas('products', [
            'sku' => 'SCH-PAINT-WTR',
            'name' => 'Water Color Paint Set 12 Colors',
            'category' => 'School Supplies',
            'price' => 110.00,
            'quantity' => 45,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'FAB-RAYON',
            'name' => 'Soft Rayon Challis Fabric (Yard)',
            'category' => 'Fabric',
            'price' => 130.00,
            'quantity' => 32,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'GEN-MUG-TRAVEL',
            'name' => 'Insulated Stainless Travel Mug 500ml',
            'category' => 'General Merchandise',
            'price' => 260.00,
            'quantity' => 40,
        ]);
    }
}
