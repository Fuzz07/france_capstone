<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatBotTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_is_redirected_to_login_on_chat_index()
    {
        $response = $this->get(route('chat.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function customer_only_sees_their_own_and_bot_or_admin_messages_on_chat()
    {
        $customer1 = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $customer2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Seed some messages
        Message::create(['user_name' => 'John Doe', 'message' => 'John message']);
        Message::create(['user_name' => 'Jane Smith', 'message' => 'Jane message']);
        Message::create(['user_name' => "Mera's Support Bot", 'message' => 'Hello John']);
        Message::create(['user_name' => 'Admin User', 'message' => 'Admin announcement']);

        // Login as Customer 1
        $this->actingAs($customer1);

        // View chat index
        $response = $this->get(route('chat.index'));
        $response->assertStatus(200);

        // Customer 1 should see:
        // - John Doe's message
        // - Bot message
        // - Admin message
        // Customer 1 should NOT see:
        // - Jane Smith's message (privacy)
        $response->assertSee('John message');
        $response->assertSee('Hello John');
        $response->assertSee('Admin announcement');
        $response->assertDontSee('Jane message');

        // Verify JSON messages API behaves the same
        $apiResponse = $this->get(route('chat.messages'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonFragment(['user_name' => 'John Doe', 'message' => 'John message']);
        $apiResponse->assertJsonFragment(['user_name' => "Mera's Support Bot", 'message' => 'Hello John']);
        $apiResponse->assertJsonFragment(['user_name' => 'Admin User', 'message' => 'Admin announcement']);
        $apiResponse->assertJsonMissing(['user_name' => 'Jane Smith', 'message' => 'Jane message']);
    }

    /** @test */
    public function customer_message_triggers_automated_bot_response()
    {
        $customer = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $this->actingAs($customer);

        // Send a greeting to trigger the bot greeting
        $response = $this->post(route('chat.store'), [
            'message' => 'Hello chatbot'
        ]);

        $response->assertRedirect(route('chat.index'));

        // Check if John's message and the bot's message exist in DB
        $this->assertDatabaseHas('messages', [
            'user_name' => 'John Doe',
            'message' => 'Hello chatbot'
        ]);

        $this->assertDatabaseHas('messages', [
            'user_name' => "Mera's Support Bot",
            'message' => "Hello! 👋 Welcome to Mera's Merchandise support assistant. How can I help you today? You can ask me about our products, store hours, location, or payment options."
        ]);
    }

    /** @test */
    public function admin_can_see_all_messages_on_chat()
    {
        $customer = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        Message::create(['user_name' => 'John Doe', 'message' => 'John message']);
        Message::create(['user_name' => "Mera's Support Bot", 'message' => 'Bot message']);

        $this->actingAs($admin);

        $response = $this->get(route('chat.index'));
        $response->assertStatus(200);
        $response->assertSee('John message');
        $response->assertSee('Bot message');

        $apiResponse = $this->get(route('chat.messages'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonFragment(['user_name' => 'John Doe', 'message' => 'John message']);
        $apiResponse->assertJsonFragment(['user_name' => "Mera's Support Bot", 'message' => 'Bot message']);
    }
}
