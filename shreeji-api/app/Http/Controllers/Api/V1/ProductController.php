<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
    ) {}

    /**
     * List products (paginated, filterable).
     * GET /api/v1/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::active()
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
                'skus' => fn ($q) => $q->active()->select('id', 'product_id', 'sku', 'selling_price', 'retail_price', 'stock_quantity', 'reserved_quantity'),
            ]);

        // Filters
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->input('category')));
        }

        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $request->input('brand')));
        }

        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->boolean('express')) {
            $query->expressEligible();
        }

        // Sort
        $sort = $request->input('sort', 'relevance');
        match ($sort) {
            'price_asc' => $query->orderByRaw('(SELECT MIN(selling_price) FROM product_skus WHERE product_skus.product_id = products.id AND is_active = 1) ASC'),
            'price_desc' => $query->orderByRaw('(SELECT MIN(selling_price) FROM product_skus WHERE product_skus.product_id = products.id AND is_active = 1) DESC'),
            'newest' => $query->orderBy('created_at', 'desc'),
            'name' => $query->orderBy('name'),
            default => $query->orderBy('is_featured', 'desc')->orderBy('sort_order'),
        };

        $perPage = min($request->input('per_page', 20), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get featured products.
     * GET /api/v1/products/featured
     */
    public function featured(): JsonResponse
    {
        $products = Product::active()
            ->featured()
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
                'skus' => fn ($q) => $q->active()->select('id', 'product_id', 'sku', 'selling_price', 'retail_price', 'stock_quantity', 'reserved_quantity'),
            ])
            ->orderBy('sort_order')
            ->limit(12)
            ->get();

        return response()->json(['data' => $products]);
    }

    /**
     * Search products via Meilisearch.
     * GET /api/v1/products/search?q=m12+4+pin+5m
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => [], 'pagination' => ['total' => 0]]);
        }

        $perPage = min($request->input('per_page', 20), 50);
        $page = $request->input('page', 1);

        // Search via Meilisearch (Scout)
        $searchResults = Product::search($query)
            ->options([
                'attributesToSearchOn' => ['name', 'search_text', 'brand', 'category', 'sku_codes'],
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
            ]);

        // Apply filters if provided
        $filters = [];
        if ($request->filled('category')) {
            $filters[] = "category_slug = '{$request->input('category')}'";
        }
        if ($request->filled('brand')) {
            $filters[] = "brand_slug = '{$request->input('brand')}'";
        }
        if ($request->boolean('in_stock')) {
            $filters[] = 'in_stock = true';
        }
        if ($request->filled('min_price')) {
            $filters[] = "min_price >= {$request->input('min_price')}";
        }
        if ($request->filled('max_price')) {
            $filters[] = "max_price <= {$request->input('max_price')}";
        }

        if (!empty($filters)) {
            $searchResults->options(['filter' => implode(' AND ', $filters)]);
        }

        $results = $searchResults->get();

        // Eager load relationships for the results
        $results->load([
            'category:id,name,slug',
            'brand:id,name,slug',
            'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
            'skus' => fn ($q) => $q->active()->select('id', 'product_id', 'sku', 'selling_price', 'retail_price', 'stock_quantity', 'reserved_quantity'),
        ]);

        return response()->json([
            'data' => $results,
            'query' => $query,
            'pagination' => [
                'total' => $results->count(), // Meilisearch returns estimated total
                'per_page' => $perPage,
                'current_page' => (int) $page,
            ],
        ]);
    }

    /**
     * Get single product by slug (full detail).
     * GET /api/v1/products/{slug}
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with([
                'category:id,name,slug,parent_id',
                'category.parent:id,name,slug',
                'brand:id,name,slug,logo',
                'images' => fn ($q) => $q->orderBy('sort_order'),
                'attributes' => fn ($q) => $q->orderBy('sort_order'),
                'skus' => fn ($q) => $q->active(),
                'skus.priceTiers',
                'compatibleProducts' => fn ($q) => $q->active()->with([
                    'images' => fn ($q2) => $q2->orderBy('sort_order')->limit(1),
                    'skus' => fn ($q2) => $q2->active()->select('id', 'product_id', 'selling_price', 'retail_price'),
                ]),
            ])
            ->firstOrFail();

        // Resolve pricing for the current user
        $user = $request->user();
        $skuPricing = [];
        foreach ($product->skus as $sku) {
            $skuPricing[$sku->id] = $this->pricingService->resolvePrice($sku, $user);
        }

        // Build breadcrumb
        $breadcrumb = collect($product->category->getAncestors())
            ->map(fn ($cat) => ['name' => $cat->name, 'slug' => $cat->slug])
            ->push(['name' => $product->category->name, 'slug' => $product->category->slug])
            ->push(['name' => $product->name, 'slug' => $product->slug]);

        // Stock status
        $stockStatus = 'out_of_stock';
        if ($product->is_in_stock) {
            $stockStatus = $product->total_stock > 10 ? 'in_stock' : 'low_stock';
        }

        return response()->json([
            'data' => $product,
            'pricing' => $skuPricing,
            'stock_status' => $stockStatus,
            'total_stock' => $product->total_stock,
            'is_express_eligible' => $product->is_express_eligible,
            'breadcrumb' => $breadcrumb,
        ]);
    }
}
