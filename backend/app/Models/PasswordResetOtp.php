<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pre-auth, email-keyed OTP rows for password reset. Intentionally has NO
 * tenant scope — the person resetting isn't logged in, so there is no tenant.
 */
class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email', 'code_hash', 'reset_token_hash',
        'attempts', 'verified_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
