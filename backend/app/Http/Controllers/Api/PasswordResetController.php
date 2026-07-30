<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Public, pre-auth OTP password reset. Three steps:
 *   1. requestOtp  — email a 6-digit code (always 200, never reveals existence)
 *   2. verifyOtp   — exchange a correct code for a one-time reset token
 *   3. resetPassword — exchange the reset token for a new password
 * Codes and tokens are only ever stored hashed.
 */
class PasswordResetController extends Controller
{
    /** POST /forgot-password */
    public function requestOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $generic = ['message' => 'If an account exists, a reset code has been sent.'];

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            // Never reveal whether the email exists.
            return response()->json($generic);
        }

        // One live code per email at a time.
        PasswordResetOtp::where('email', $data['email'])->delete();

        $code = (string) random_int(100000, 999999);

        PasswordResetOtp::create([
            'email' => $data['email'],
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($data['email'])->send(new OtpCodeMail($user->name, $code, 10));

        return response()->json($generic);
    }

    /** POST /verify-otp */
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string'],
        ]);

        $otp = PasswordResetOtp::where('email', $data['email'])
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp) {
            return response()->json(['message' => 'This code has expired. Request a new one.'], 422);
        }

        $otp->increment('attempts');

        if ($otp->attempts > 5) {
            return response()->json(['message' => 'Too many attempts. Request a new code.'], 429);
        }

        if (! Hash::check($data['otp'], $otp->code_hash)) {
            return response()->json(['message' => 'That code is not correct.'], 422);
        }

        $token = Str::random(64);

        $otp->update([
            'reset_token_hash' => Hash::make($token),
            'verified_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        return response()->json(['reset_token' => $token]);
    }

    /** POST /reset-password */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $invalid = ['message' => 'This reset link is invalid or has expired.'];

        $otp = PasswordResetOtp::where('email', $data['email'])
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || ! $otp->reset_token_hash || ! Hash::check($data['reset_token'], $otp->reset_token_hash)) {
            return response()->json($invalid, 422);
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return response()->json($invalid, 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        PasswordResetOtp::where('email', $data['email'])->delete();

        return response()->json(['message' => 'Your password has been reset.']);
    }
}
