<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    /**
     * Submit a bulk quote request.
     * POST /api/v1/quotes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'gstin' => ['sometimes', 'string', 'size:15'],
            'message' => ['sometimes', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.sku_id' => ['sometimes', 'integer', 'exists:product_skus,id'],
        ]);

        $quoteRequest = QuoteRequest::create([
            'user_id' => $request->user()?->id,
            'company_name' => $validated['company_name'],
            'contact_name' => $validated['contact_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'gstin' => $validated['gstin'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($validated['items'] as $item) {
            $quoteRequest->items()->create([
                'product_description' => $item['product_description'],
                'quantity' => $item['quantity'],
                'sku_id' => $item['sku_id'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Quote request submitted successfully. We will get back to you within 24 hours.',
            'data' => $quoteRequest->load('items'),
        ], 201);
    }

    /**
     * List user's quote requests.
     * GET /api/v1/quotes
     */
    public function index(Request $request): JsonResponse
    {
        $quotes = QuoteRequest::where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $quotes->items(),
            'pagination' => [
                'current_page' => $quotes->currentPage(),
                'last_page' => $quotes->lastPage(),
                'total' => $quotes->total(),
            ],
        ]);
    }

    /**
     * Get single quote request.
     * GET /api/v1/quotes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $quote = QuoteRequest::where('user_id', $request->user()->id)
            ->with('items.sku.product')
            ->findOrFail($id);

        return response()->json(['data' => $quote]);
    }
}
