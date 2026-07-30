<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InviteMail;
use App\Models\Invitation;
use App\Models\School;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    /**
     * Admin creates an invite. We generate a one-time token and return a link.
     * The invited person sets THEIR OWN password via /accept — so there is no
     * shared default password anywhere in the system.
     * (Emailing the link is a queued job later; for now we return it.)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in(['teacher', 'student', 'parent'])],
        ]);

        // Don't invite someone who already has an account.
        if (User::where('email', $data['email'])->exists()) {
            return response()->json(['message' => 'A user with this email already exists.'], 409);
        }

        $invitation = Invitation::create([
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            // school_id auto-stamped by BelongsToSchool from the current tenant.
        ]);

        $acceptUrl = rtrim(config('app.frontend_url', ''), '/') . '/accept-invite?token=' . $invitation->token;

        // Deliver the invite by email. Wrapped so a mail failure never blocks
        // the invite being created (the link is also returned in the response).
        try {
            $schoolName = optional(School::find($invitation->school_id))->name;
            Mail::to($invitation->email)->send(new InviteMail($invitation->email, $schoolName, $acceptUrl));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'invitation' => $invitation->only('id', 'email', 'role', 'expires_at'),
            'accept_url' => $acceptUrl,
            'token' => $invitation->token, // returned for now; delivered by email later
        ], 201);
    }

    /**
     * Public: the invited person accepts, choosing their own password. This
     * creates their user account and logs them in.
     */
    public function accept(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Look up WITHOUT the tenant scope — the accepting user isn't logged in
        // yet, so there is no current tenant. The token itself carries the school.
        $invitation = Invitation::withoutGlobalScope('school')
            ->where('token', $data['token'])->first();

        if (! $invitation || ! $invitation->isPending()) {
            return response()->json(['message' => 'This invitation is invalid or has expired.'], 410);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => Hash::make($data['password']),
            'role' => $invitation->role,
            'status' => 'active',
            'school_id' => $invitation->school_id,
        ]);

        $invitation->update(['accepted_at' => now()]);

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user->only('id', 'name', 'email', 'role', 'school_id'),
        ], 201);
    }
}
