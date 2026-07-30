<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\PasswordResetOtp;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'user@alpha.test'): User
    {
        $school = School::create(['name' => 'Alpha']);

        return User::create([
            'name' => 'Ada', 'email' => $email, 'password' => bcrypt('old-password-1'),
            'role' => 'school_admin', 'status' => 'active', 'school_id' => $school->id,
        ]);
    }

    /** Insert an OTP row with a code we know, so we can drive verify/reset. */
    private function seedOtp(string $email, string $code = '123456', array $overrides = []): PasswordResetOtp
    {
        return PasswordResetOtp::create(array_merge([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ], $overrides));
    }

    public function test_forgot_password_sends_otp_for_existing_user(): void
    {
        Mail::fake();
        $this->makeUser();

        $this->postJson('/api/forgot-password', ['email' => 'user@alpha.test'])
            ->assertOk()
            ->assertJson(['message' => 'If an account exists, a reset code has been sent.']);

        Mail::assertSent(OtpCodeMail::class);
        $this->assertDatabaseHas('password_reset_otps', ['email' => 'user@alpha.test']);
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        Mail::fake();

        $this->postJson('/api/forgot-password', ['email' => 'nobody@alpha.test'])
            ->assertOk()
            ->assertJson(['message' => 'If an account exists, a reset code has been sent.']);

        Mail::assertNotSent(OtpCodeMail::class);
        $this->assertDatabaseMissing('password_reset_otps', ['email' => 'nobody@alpha.test']);
    }

    public function test_full_reset_happy_path(): void
    {
        $this->makeUser();
        $this->seedOtp('user@alpha.test', '123456');

        // Verify with the correct OTP -> reset_token.
        $token = $this->postJson('/api/verify-otp', [
            'email' => 'user@alpha.test', 'otp' => '123456',
        ])->assertOk()->assertJsonStructure(['reset_token'])->json('reset_token');

        // Reset the password with the token.
        $this->postJson('/api/reset-password', [
            'email' => 'user@alpha.test', 'reset_token' => $token, 'password' => 'brand-new-pass-1',
        ])->assertOk()->assertJson(['message' => 'Your password has been reset.']);

        // The old code row is gone, and the user can log in with the new password.
        $this->assertDatabaseMissing('password_reset_otps', ['email' => 'user@alpha.test']);
        $this->postJson('/api/login', [
            'email' => 'user@alpha.test', 'password' => 'brand-new-pass-1',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $this->makeUser();
        $this->seedOtp('user@alpha.test', '123456');

        $this->postJson('/api/verify-otp', [
            'email' => 'user@alpha.test', 'otp' => '000000',
        ])->assertStatus(422)->assertJson(['message' => 'That code is not correct.']);
    }

    public function test_expired_code_returns_422(): void
    {
        $this->makeUser();
        $this->seedOtp('user@alpha.test', '123456', ['expires_at' => now()->subMinute()]);

        $this->postJson('/api/verify-otp', [
            'email' => 'user@alpha.test', 'otp' => '123456',
        ])->assertStatus(422)->assertJson(['message' => 'This code has expired. Request a new one.']);
    }

    public function test_too_many_attempts_returns_429(): void
    {
        $this->makeUser();
        // attempts already at 5; the next wrong try pushes attempts to 6 (> 5).
        $this->seedOtp('user@alpha.test', '123456', ['attempts' => 5]);

        $this->postJson('/api/verify-otp', [
            'email' => 'user@alpha.test', 'otp' => '000000',
        ])->assertStatus(429)->assertJson(['message' => 'Too many attempts. Request a new code.']);
    }

    public function test_reset_with_bad_token_is_rejected(): void
    {
        $this->makeUser();
        $this->seedOtp('user@alpha.test', '123456', [
            'reset_token_hash' => Hash::make('the-real-token'),
            'verified_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->postJson('/api/reset-password', [
            'email' => 'user@alpha.test', 'reset_token' => 'wrong-token', 'password' => 'brand-new-pass-1',
        ])->assertStatus(422)->assertJson(['message' => 'This reset link is invalid or has expired.']);
    }
}
