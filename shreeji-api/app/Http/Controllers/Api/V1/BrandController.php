<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    /**
     * List all active brands.
     * GET /api/v1/brands
     */
    public function index(): JsonResponse
    {
        $brands = Brand::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'logo', 'website']);

        return response()->json(['data' => $brands]);
    }

    /**
     * Get single brand by slug with its products.
     * GET /api/v1/brands/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $brand = Brand::active()
            ->where('slug', $slug)
            ->withCount(['products' => fn ($q) => $q->active()])
            ->firstOrFail();

        return response()->json(['data' => $brand]);
    }
}
