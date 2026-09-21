<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List all active categories (flat list).
     * GET /api/v1/categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->ordered()
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get(['id', 'parent_id', 'name', 'slug', 'icon', 'image', 'sort_order']);

        return response()->json(['data' => $categories]);
    }

    /**
     * Get full category tree (nested children).
     * GET /api/v1/categories/tree
     */
    public function tree(): JsonResponse
    {
        $tree = Category::active()
            ->roots()
            ->ordered()
            ->with(['childrenRecursive' => function ($q) {
                $q->active()->ordered();
            }])
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get(['id', 'parent_id', 'name', 'slug', 'icon', 'image', 'description', 'sort_order']);

        return response()->json(['data' => $tree]);
    }

    /**
     * Get single category by slug.
     * GET /api/v1/categories/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::active()
            ->where('slug', $slug)
            ->with(['parent:id,name,slug', 'children' => function ($q) {
                $q->active()->ordered()->withCount(['products' => fn ($q) => $q->active()]);
            }])
            ->with('attributeDefinitions')
            ->withCount(['products' => fn ($q) => $q->active()])
            ->firstOrFail();

        // Build breadcrumb
        $breadcrumb = collect($category->getAncestors())
            ->map(fn ($cat) => ['name' => $cat->name, 'slug' => $cat->slug])
            ->push(['name' => $category->name, 'slug' => $category->slug]);

        return response()->json([
            'data' => $category,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * Get products in a category (with filters, sort, pagination).
     * GET /api/v1/categories/{slug}/products
     */
    public function products(Request $request, string $slug): JsonResponse
    {
        $category = Category::active()->where('slug', $slug)->firstOrFail();

        // Include products from child categories too
        $categoryIds = collect([$category->id]);
        $childIds = Category::where('parent_id', $category->id)->active()->pluck('id');
        $categoryIds = $categoryIds->merge($childIds);

        $query = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->with([
                'brand:id,name,slug',
                'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
                'skus' => fn ($q) => $q->active()->select('id', 'product_id', 'selling_price', 'retail_price', 'stock_quantity', 'reserved_quantity'),
                'attributes' => fn ($q) => $q->where('is_filterable', true),
            ]);

        // ── Filters ────────────────────────────────────
        if ($request->filled('brand')) {
            $brandSlugs = explode(',', $request->input('brand'));
            $query->whereHas('brand', fn ($q) => $q->whereIn('slug', $brandSlugs));
        }

        if ($request->filled('min_price')) {
            $query->whereHas('skus', fn ($q) => $q->active()->where('selling_price', '>=', $request->input('min_price')));
        }

        if ($request->filled('max_price')) {
            $query->whereHas('skus', fn ($q) => $q->active()->where('selling_price', '<=', $request->input('max_price')));
        }

        if ($request->filled('in_stock')) {
            $query->inStock();
        }

        if ($request->filled('express')) {
            $query->expressEligible();
        }

        // Dynamic attribute filters (e.g., ?attr_capacity=16GB&attr_type=DDR4)
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'attr_')) {
                $attrKey = substr($key, 5);
                $attrValues = explode(',', $value);
                $query->whereHas('attributes', function ($q) use ($attrKey, $attrValues) {
                    $q->where('attribute_key', $attrKey)
                      ->whereIn('attribute_value', $attrValues);
                });
            }
        }

        // ── Sort ───────────────────────────────────────
        $sort = $request->input('sort', 'relevance');
        match ($sort) {
            'price_asc' => $query->orderByRaw('(SELECT MIN(selling_price) FROM product_skus WHERE product_skus.product_id = products.id AND is_active = 1) ASC'),
            'price_desc' => $query->orderByRaw('(SELECT MIN(selling_price) FROM product_skus WHERE product_skus.product_id = products.id AND is_active = 1) DESC'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('sort_order')->orderBy('is_featured', 'desc'),
        };

        $perPage = min($request->input('per_page', 20), 50);
        $products = $query->paginate($perPage);

        // ── Available Filters (for sidebar) ────────────
        $availableFilters = $this->getAvailableFilters($categoryIds);

        return response()->json([
            'data' => $products->items(),
            'filters' => $availableFilters,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Build available filter options for sidebar (brands, price range, attributes).
     */
    private function getAvailableFilters($categoryIds): array
    {
        // Brands in this category
        $brands = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->with('brand:id,name,slug')
            ->get()
            ->pluck('brand')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($b) => ['name' => $b->name, 'slug' => $b->slug]);

        // Price range
        $priceRange = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->join('product_skus', 'products.id', '=', 'product_skus.product_id')
            ->where('product_skus.is_active', true)
            ->selectRaw('MIN(product_skus.selling_price) as min_price, MAX(product_skus.selling_price) as max_price')
            ->first();

        // Filterable attributes
        $attributes = \App\Models\ProductAttribute::whereHas('product', function ($q) use ($categoryIds) {
                $q->active()->whereIn('category_id', $categoryIds);
            })
            ->where('is_filterable', true)
            ->get()
            ->groupBy('attribute_key')
            ->map(function ($group, $key) {
                return [
                    'key' => $key,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'values' => $group->pluck('attribute_value')->unique()->sort()->values(),
                ];
            })
            ->values();

        return [
            'brands' => $brands,
            'price_range' => [
                'min' => (float) ($priceRange->min_price ?? 0),
                'max' => (float) ($priceRange->max_price ?? 0),
            ],
            'attributes' => $attributes,
        ];
    }
}
