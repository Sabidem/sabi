<?php

namespace Tests\Feature;

use App\Mail\InviteMail;
use App\Mail\WelcomeMail;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionalMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_sends_welcome_email(): void
    {
        Mail::fake();

        $this->postJson('/api/signup', [
            'name' => 'New Admin',
            'email' => 'newadmin@alpha.test',
            'password' => 'a-strong-pass-1',
            'role' => 'school_admin',
            'school_name' => 'Alpha School',
        ])->assertCreated();

        Mail::assertSent(WelcomeMail::class);
    }

    public function test_invite_sends_invite_email(): void
    {
        Mail::fake();

        $school = School::create(['name' => 'Alpha']);
        Sanctum::actingAs(User::create([
            'name' => 'Admin', 'email' => 'admin@alpha.test', 'password' => bcrypt('password'),
            'role' => 'school_admin', 'status' => 'active', 'school_id' => $school->id,
        ]));

        $this->postJson('/api/invitations', [
            'email' => 'teacher@alpha.test', 'role' => 'teacher',
        ])->assertCreated();

        Mail::assertSent(InviteMail::class, function (InviteMail $mail) {
            return $mail->inviteeEmail === 'teacher@alpha.test'
                && $mail->schoolName === 'Alpha';
        });
    }
}
