<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * List user's addresses.
     * GET /api/v1/addresses
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $addresses]);
    }

    /**
     * Create a new address.
     * POST /api/v1/addresses
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:50'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'string', 'max:255'],
            'landmark' => ['sometimes', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'latitude' => ['sometimes', 'numeric'],
            'longitude' => ['sometimes', 'numeric'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        // If this is the first address, make it default
        if ($request->user()->addresses()->count() === 0) {
            $validated['is_default'] = true;
        }

        $address = $request->user()->addresses()->create($validated);

        return response()->json([
            'message' => 'Address created',
            'data' => $address,
        ], 201);
    }

    /**
     * Get single address.
     * GET /api/v1/addresses/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);

        return response()->json(['data' => $address]);
    }

    /**
     * Update an address.
     * PUT /api/v1/addresses/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:50'],
            'contact_name' => ['sometimes', 'string', 'max:255'],
            'contact_phone' => ['sometimes', 'string', 'regex:/^[6-9]\d{9}$/'],
            'address_line_1' => ['sometimes', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'string', 'max:255'],
            'landmark' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'pincode' => ['sometimes', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'latitude' => ['sometimes', 'numeric'],
            'longitude' => ['sometimes', 'numeric'],
        ]);

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated',
            'data' => $address->fresh(),
        ]);
    }

    /**
     * Delete an address.
     * DELETE /api/v1/addresses/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json(['message' => 'Address deleted']);
    }

    /**
     * Set an address as default.
     * POST /api/v1/addresses/{id}/set-default
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default address updated',
            'data' => $address->fresh(),
        ]);
    }
}
