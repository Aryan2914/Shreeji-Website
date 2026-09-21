<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductSku;

echo "\n==========================================================\n";
echo "1. TESTING MEILISEARCH QUERIES WITH SYNONYMS\n";
echo "==========================================================\n";

$queries = [
    'm12 4 pin 5m',
    '16gb ddr4',
    'nvme 1tb',
    'cat6 patch cord',
];

foreach ($queries as $q) {
    echo "\n--- Query: \"$q\" ---\n";
    $results = Product::search($q)->get();
    echo "Found " . $results->count() . " results:\n";
    foreach ($results as $product) {
        $skus = $product->skus->map(fn($s) => "{$s->sku} (Rs.{$s->selling_price}, Stock:{$s->stock_quantity})")->implode(', ');
        echo "  - [ID: {$product->id}] {$product->name} (Brand: {$product->brand?->name}, Category: {$product->category?->name})\n";
        echo "    SKUs: $skus\n";
    }
}

echo "\n==========================================================\n";
echo "2. TESTING DYNAMIC INDEX UPDATE ON PRICE & STOCK CHANGE\n";
echo "==========================================================\n";

$targetProduct = Product::where('slug', 'like', '%ddr4%')->first() ?? Product::first();
$targetSku = $targetProduct->skus()->first();

echo "Initial Target Product: {$targetProduct->name}\n";
echo "Initial SKU ({$targetSku->sku}): Price = Rs. {$targetSku->selling_price}, Stock = {$targetSku->stock_quantity}\n";

// Query Meilisearch before update
$searchBefore = Product::search($targetProduct->name)->raw();
$hitBefore = collect($searchBefore['hits'])->firstWhere('id', $targetProduct->id);
echo "Meilisearch Initial Index Record -> min_price: Rs. {$hitBefore['min_price']}, in_stock: " . ($hitBefore['in_stock'] ? 'YES' : 'NO') . "\n";

// Update Price and Stock
echo "\nUpdating SKU Price to Rs. 9999.00 and Stock to 0...\n";
$targetSku->update([
    'selling_price' => 9999.00,
    'stock_quantity' => 0,
    'reserved_quantity' => 0,
]);

// Trigger Scout sync
$targetProduct->touch();
$targetProduct->searchable();

// Wait 500ms for Meilisearch task queue to process
usleep(500000);

// Query Meilisearch after update
$searchAfter = Product::search($targetProduct->name)->raw();
$hitAfter = collect($searchAfter['hits'])->firstWhere('id', $targetProduct->id);

echo "Meilisearch Updated Index Record -> min_price: Rs. {$hitAfter['min_price']}, in_stock: " . ($hitAfter['in_stock'] ? 'YES' : 'NO') . "\n";

if ($hitAfter['min_price'] == 9999.00 && $hitAfter['in_stock'] === false) {
    echo ">>> RESULT: PASS! Meilisearch index dynamically updated reflecting new price and stock availability.\n";
} else {
    echo ">>> RESULT: FAIL! Meilisearch index did not reflect updates.\n";
}
