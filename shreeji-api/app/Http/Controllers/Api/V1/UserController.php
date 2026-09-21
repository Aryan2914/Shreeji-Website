<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get current user profile.
     * GET /api/v1/user
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'businessProfile',
            'addresses' => fn ($q) => $q->orderByDesc('is_default'),
        ]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'account_type' => $user->account_type,
                'is_admin' => $user->is_admin,
                'business_profile' => $user->businessProfile,
                'addresses' => $user->addresses,
                'orders_count' => $user->orders()->count(),
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update user profile.
     * PUT /api/v1/user
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['sometimes', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone,' . $user->id],
            'current_password' => ['required_with:new_password', 'string'],
            'new_password' => ['sometimes', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ]);

        // Verify current password if changing password
        if (isset($validated['current_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 422);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        if (isset($validated['name'])) $user->name = $validated['name'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        if (isset($validated['phone'])) $user->phone = $validated['phone'];

        $user->save();

        return response()->json([
            'message' => 'Profile updated',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Update or create business profile.
     * PUT /api/v1/user/business-profile
     */
    public function updateBusinessProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'gstin' => ['required', 'string', 'size:15'],
            'pan' => ['sometimes', 'string', 'size:10'],
        ]);

        if (!BusinessProfile::isValidGstin($validated['gstin'])) {
            return response()->json(['message' => 'Invalid GSTIN format'], 422);
        }

        $user = $request->user();

        // Switch account type to business if personal
        if ($user->account_type->value === 'personal') {
            $user->update(['account_type' => 'business']);
        }

        $user->businessProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $validated['company_name'],
                'gstin' => strtoupper($validated['gstin']),
                'pan' => isset($validated['pan']) ? strtoupper($validated['pan']) : null,
                'price_tier' => 'business',
            ]
        );

        return response()->json([
            'message' => 'Business profile updated',
            'data' => $user->fresh()->load('businessProfile'),
        ]);
    }
}
