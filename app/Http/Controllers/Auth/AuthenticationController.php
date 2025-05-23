<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|ir_mobile:zero',
        ]);

        $verification = PhoneVerification::generate($request->phone);

        $verification->send(); // Send the OTP

        return response()->json(['message' => __('OTP sent successfully')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|ir_mobile:zero',
            'code' => 'required|digits:6',
        ]);

        if (!PhoneVerification::validate($request->phone, $request->code)) {
            return response()->json(['message' => __('Invalid or expired verification code.')], 401);
        }

        // This automatically handles both cases correctly
        $user = User::firstOrCreate(['phone' => $request->phone]);

        // Mark phone as verified (important for new registrations)
        if (!$user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('Logged in successfully'),
            'user' => $user
                ->only(['id', 'name', 'phone']),
            'token' => $token,
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => __('Logged out successfully')]);
    }

    public function findUser(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|ir_mobile:zero',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['message' => __('User not found')], 404);
        }

        return response()->json($user->only(['name', 'phone']));
    }
}
