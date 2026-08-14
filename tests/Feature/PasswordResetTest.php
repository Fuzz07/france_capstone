<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Reset Tester',
            'email' => 'reset_test_' . uniqid() . '@example.com',
            'password' => Hash::make('original-password'),
            'role' => 'user',
        ]);
    }

    /**
     * Pulls the plaintext token out of the reset URL that was mailed to the user.
     */
    private function requestResetToken(User $user): string
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();

        $token = null;

        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user, &$token) {
            parse_str(parse_url($mail->resetUrl, PHP_URL_QUERY) ?? '', $query);
            $token = $query['token'] ?? null;

            return $mail->hasTo($user->email) && $token !== null;
        });

        $this->assertNotNull($token, 'No reset token was present in the mailed URL.');

        return $token;
    }

    /** @test */
    public function requesting_a_link_stores_a_hashed_token_and_mails_the_user()
    {
        $user = $this->makeUser();

        $token = $this->requestResetToken($user);

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        $this->assertNotNull($record, 'No password_reset_tokens row was created.');
        $this->assertNotSame($token, $record->token, 'The token was stored in plaintext.');
        $this->assertTrue(Hash::check($token, $record->token));
    }

    /** @test */
    public function requesting_a_link_for_an_unknown_email_is_rejected()
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    /** @test */
    public function a_valid_token_opens_the_reset_form()
    {
        $user = $this->makeUser();
        $token = $this->requestResetToken($user);

        $this->get('/reset-password?' . http_build_query([
            'token' => $token,
            'email' => $user->email,
        ]))->assertStatus(200)->assertViewIs('auth.reset-password');
    }

    /** @test */
    public function the_password_is_updated_and_the_token_is_consumed()
    {
        $user = $this->makeUser();
        $token = $this->requestResetToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue(Hash::check('brand-new-password', $user->password));
        $this->assertSame(
            0,
            DB::table('password_reset_tokens')->where('email', $user->email)->count(),
            'The token should be deleted once it has been used.'
        );
    }

    /** @test */
    public function a_used_token_cannot_be_replayed()
    {
        $user = $this->makeUser();
        $token = $this->requestResetToken($user);

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ];

        $this->post('/reset-password', $payload);

        $this->post('/reset-password', array_merge($payload, [
            'password' => 'second-attempt',
            'password_confirmation' => 'second-attempt',
        ]))->assertRedirect(route('password.request'));

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
    }

    /** @test */
    public function a_forged_token_is_rejected()
    {
        $user = $this->makeUser();
        $this->requestResetToken($user);

        $this->post('/reset-password', [
            'token' => Str::random(60),
            'email' => $user->email,
            'password' => 'attacker-password',
            'password_confirmation' => 'attacker-password',
        ])->assertRedirect(route('password.request'));

        $user->refresh();
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    /** @test */
    public function an_expired_token_is_rejected()
    {
        $user = $this->makeUser();
        $token = $this->requestResetToken($user);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'too-late-password',
            'password_confirmation' => 'too-late-password',
        ])->assertRedirect(route('password.request'));

        $user->refresh();
        $this->assertTrue(Hash::check('original-password', $user->password));
    }
}
