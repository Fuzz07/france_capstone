<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BulkPricingAndHandoffTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::create([
            'name' => 'Bulk Buyer',
            'email' => 'bulk.buyer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Store Admin',
            'email' => 'store.admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    }

    private function bulkProduct(): Product
    {
        return Product::create([
            'sku' => 'SKU-BULK',
            'name' => 'Detergent Bar',
            'category' => 'Household',
            'price' => 25.00,
            'bulk_price' => 20.00,
            'bulk_min_qty' => 12,
            'quantity' => 100,
        ]);
    }

    /** @test */
    public function chatbot_offers_messenger_handoff_on_the_third_message_only()
    {
        $this->actingAs($this->customer());

        $first = $this->postJson(route('chat.bot-response'), ['message' => 'hello']);
        $first->assertStatus(200);
        $this->assertNull($first->json('handoff'), 'Handoff must not be offered on the first message.');

        $second = $this->postJson(route('chat.bot-response'), ['message' => 'what are your hours?']);
        $this->assertNull($second->json('handoff'), 'Handoff must not be offered on the second message.');

        $third = $this->postJson(route('chat.bot-response'), ['message' => 'where are you located?']);
        $this->assertNotNull($third->json('handoff'), 'Handoff must be offered on the third message.');
        $this->assertSame('https://m.me/JohhFranceDescartinQuijano', $third->json('handoff.url'));
        $this->assertSame("Mera's Merchandise", $third->json('handoff.name'));

        // Offered once; the bot keeps answering normally afterwards.
        $fourth = $this->postJson(route('chat.bot-response'), ['message' => 'do you accept gcash?']);
        $this->assertNull($fourth->json('handoff'), 'Handoff must only be offered once per session.');
        $this->assertStringContainsString('GCash', $fourth->json('reply'));
    }

    /** @test */
    public function pos_charges_bulk_price_once_the_threshold_is_reached()
    {
        $this->actingAs($this->admin());
        $product = $this->bulkProduct();

        // Below the threshold the line stays on the retail price.
        $this->post(route('pos.add'), ['product_id' => $product->id, 'qty' => 11]);
        $line = session('cart')[$product->id];
        $this->assertSame(25.0, $line['price']);
        $this->assertFalse($line['is_bulk']);

        // Reaching the threshold flips the whole line to the bulk price.
        $this->post(route('pos.updateCart'), ['id' => $product->id, 'action' => 'increase']);
        $line = session('cart')[$product->id];
        $this->assertSame(12, $line['qty']);
        $this->assertSame(20.0, $line['price']);
        $this->assertTrue($line['is_bulk']);

        // Dropping back below it returns the line to retail.
        $this->post(route('pos.updateCart'), ['id' => $product->id, 'action' => 'decrease']);
        $line = session('cart')[$product->id];
        $this->assertSame(11, $line['qty']);
        $this->assertSame(25.0, $line['price']);
        $this->assertFalse($line['is_bulk']);
    }

    /** @test */
    public function checkout_totals_use_the_bulk_price()
    {
        $this->actingAs($this->admin());
        $product = $this->bulkProduct();

        $this->post(route('pos.add'), ['product_id' => $product->id, 'qty' => 12]);
        $this->post(route('pos.checkout'), ['cash_tendered' => 500, 'payment_method' => 'cash']);

        // 12 x 20.00 bulk, not 12 x 25.00 retail.
        $this->assertDatabaseHas('sales', ['total' => 240.00]);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'qty' => 12,
            'price' => 20.00,
        ]);
    }

    /** @test */
    public function products_without_bulk_pricing_always_use_the_retail_price()
    {
        $this->actingAs($this->admin());

        $product = Product::create([
            'sku' => 'SKU-PLAIN',
            'name' => 'Ballpen',
            'price' => 15.00,
            'quantity' => 100,
        ]);

        $this->post(route('pos.add'), ['product_id' => $product->id, 'qty' => 50]);
        $line = session('cart')[$product->id];

        $this->assertSame(15.0, $line['price']);
        $this->assertFalse($line['is_bulk']);
    }

    /** @test */
    public function bulk_price_requires_a_matching_minimum_quantity()
    {
        $this->actingAs($this->admin());

        $response = $this->post(route('products.store'), [
            'name' => 'Half Configured',
            'price' => 10.00,
            'quantity' => 5,
            'bulk_price' => 8.00,
        ]);

        $response->assertSessionHasErrors('bulk_min_qty');
        $this->assertDatabaseMissing('products', ['name' => 'Half Configured']);
    }

    /** @test */
    public function pos_purchase_all_adds_entire_remaining_stock_and_applies_bulk_pricing()
    {
        $this->actingAs($this->admin());
        $product = $this->bulkProduct(); // 100 in stock, bulk_min_qty = 12, retail 25, bulk 20

        $response = $this->post(route('pos.add'), [
            'product_id' => $product->id,
            'purchase_all' => 1,
        ]);

        $response->assertStatus(302);
        $cart = session('cart');
        $this->assertArrayHasKey($product->id, $cart);
        $this->assertSame(100, $cart[$product->id]['qty']);
        $this->assertSame(20.0, $cart[$product->id]['price']);
        $this->assertTrue($cart[$product->id]['is_bulk']);
    }

    /** @test */
    public function pos_admin_catalog_is_paginated_by_eight()
    {
        $this->actingAs($this->admin());

        // Create 10 products
        for ($i = 1; $i <= 10; $i++) {
            Product::create([
                'sku' => "SKU-TEST-{$i}",
                'name' => "Test Product {$i}",
                'price' => 10.00,
                'quantity' => 20,
            ]);
        }

        $response = $this->get(route('pos.index'));
        $response->assertStatus(200);

        $paginator = $response->viewData('products');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $this->assertSame(8, $paginator->perPage());
        $this->assertCount(8, $paginator->items());
    }

    /** @test */
    public function inventory_is_paginated_by_eight()
    {
        $this->actingAs($this->admin());

        for ($i = 1; $i <= 10; $i++) {
            Product::create([
                'sku' => "SKU-INV-{$i}",
                'name' => "Inv Product {$i}",
                'price' => 15.00,
                'quantity' => 10,
            ]);
        }

        $response = $this->get(route('products.index'));
        $response->assertStatus(200);

        $paginator = $response->viewData('products');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $this->assertSame(8, $paginator->perPage());
        $this->assertCount(8, $paginator->items());
    }

    /** @test */
    public function user_management_is_paginated_by_eight()
    {
        $this->actingAs($this->admin());

        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => "Customer {$i}",
                'email' => "customer{$i}@test.com",
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]);
        }

        $response = $this->get(route('users.index'));
        $response->assertStatus(200);

        $paginator = $response->viewData('users');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $this->assertSame(8, $paginator->perPage());
        $this->assertCount(8, $paginator->items());
    }

    /** @test */
    public function activity_logs_are_paginated_by_six()
    {
        $this->actingAs($this->admin());

        for ($i = 1; $i <= 10; $i++) {
            \App\Models\ActivityLog::create([
                'user_id' => 1,
                'user_name' => 'Admin User',
                'user_role' => 'admin',
                'action' => "action_{$i}",
                'description' => "Test action {$i}",
                'ip_address' => '127.0.0.1',
            ]);
        }

        $response = $this->get(route('logs.index'));
        $response->assertStatus(200);

        $paginator = $response->viewData('logs');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
        $this->assertSame(6, $paginator->perPage());
        $this->assertCount(6, $paginator->items());
    }
}
