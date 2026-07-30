<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\LoginHistory;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Public sign-up. A school_admin registers their school (creating the School
     * + the owner account); a creator self-registers with no school. Other roles
     * (teacher/student/parent) join via invitations, not signup.
     */
    public function signup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', Rule::in(['school_admin', 'creator'])],
            'school_name' => ['nullable', 'string', 'max:160'],
        ]);

        $role = $data['role'] ?? 'school_admin';
        $schoolId = null;

        if ($role === 'school_admin') {
            $school = School::create(['name' => $data['school_name'] ?: ($data['name'] . "'s School")]);
            $schoolId = $school->id;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'status' => 'active',
            'school_id' => $schoolId,
        ]);

        // Warm welcome. Wrapped so a mail failure never blocks sign-up.
        try {
            Mail::to($user->email)->send(new WelcomeMail($user->name));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user->only('id', 'name', 'email', 'role', 'school_id'),
        ], 201);
    }

    /** Exchange email + password for a Sanctum API token. */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Same message for both cases so we don't reveal which emails exist.
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'This account is not active.'], 403);
        }

        // Record the successful login. Wrapped so an audit failure never blocks
        // sign-in. school_id is passed explicitly: no tenant is resolved yet on
        // this public route, so the BelongsToSchool auto-stamp has nothing to read.
        try {
            LoginHistory::create([
                'school_id' => $user->school_id,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'logged_in_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user->only('id', 'name', 'email', 'role', 'school_id'),
        ];
    }

    public function me(Request $request)
    {
        return $request->user()->only('id', 'name', 'email', 'role', 'school_id', 'status');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
