<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FacadeAliasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * config/app.php once listed only App/Auth/Route, which replaced the framework
     * defaults wholesale and made every other root-namespace facade call fatal.
     *
     * @test
     */
    public function root_namespace_facade_aliases_are_registered()
    {
        $aliases = config('app.aliases');

        foreach (['Log', 'Mail', 'DB', 'Str', 'App', 'Auth', 'Route'] as $alias) {
            $this->assertArrayHasKey($alias, $aliases, "Missing facade alias: {$alias}");
        }

        $this->assertTrue(class_exists('Log'), 'The \Log alias must resolve.');
    }

    /**
     * Replying to an inquiry when mail delivery fails must log and carry on, not
     * blow up inside its own catch block.
     *
     * @test
     */
    public function replying_to_an_inquiry_survives_a_mail_failure()
    {
        $admin = User::create([
            'name' => 'Store Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $inquiry = Inquiry::create([
            'customer_name' => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'subject' => 'Stock question',
            'message' => 'Do you have detergent in stock?',
            'status' => 'pending',
        ]);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));

        $response = $this->actingAs($admin)->post(route('inquiries.respond', $inquiry), [
            'response' => 'Yes, we have plenty in stock.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'responded',
            'response' => 'Yes, we have plenty in stock.',
        ]);
    }
}
