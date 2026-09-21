<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:users'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'account_type' => ['sometimes', 'in:personal,business'],
            // Business fields (required if account_type = business)
            'company_name' => ['required_if:account_type,business', 'string', 'max:255'],
            'gstin' => ['required_if:account_type,business', 'string', 'size:15'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'account_type' => $validated['account_type'] ?? 'personal',
        ]);

        // Create business profile if business account
        if (($validated['account_type'] ?? 'personal') === 'business') {
            if (!BusinessProfile::isValidGstin($validated['gstin'])) {
                $user->delete();
                throw ValidationException::withMessages([
                    'gstin' => ['Invalid GSTIN format.'],
                ]);
            }

            $user->businessProfile()->create([
                'company_name' => $validated['company_name'],
                'gstin' => strtoupper($validated['gstin']),
                'price_tier' => 'business',
                'is_verified' => false,
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'account_type' => $user->account_type,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login with email/phone + password.
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'], // email or phone
            'password' => ['required', 'string'],
        ]);

        // Find user by email or phone
        $user = User::where('email', $validated['login'])
            ->orWhere('phone', $validated['login'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Your account has been deactivated. Please contact support.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'account_type' => $user->account_type,
                'is_admin' => $user->is_admin,
                'business_profile' => $user->isBusiness() ? $user->businessProfile : null,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout (revoke current token).
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Refresh token (revoke current, issue new).
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    /**
     * Request password reset.
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // TODO: Send password reset email/OTP
        // For now, return success message
        return response()->json([
            'message' => 'Password reset link sent to your email.',
        ]);
    }

    /**
     * Reset password.
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        // TODO: Verify token and reset password
        return response()->json([
            'message' => 'Password reset successful.',
        ]);
    }
}
