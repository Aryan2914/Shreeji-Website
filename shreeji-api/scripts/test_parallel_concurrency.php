<?php

/**
 * Parallel Concurrency Stress Test on MySQL
 * Spawns truly parallel OS processes using proc_open / popen to test lockForUpdate()
 * and atomic sequence generation.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductSku;
use App\Models\User;
use App\Models\Address;
use App\Models\Order;
use App\Models\CartItem;
use App\Services\CheckoutService;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;

$action = $argv[1] ?? 'main';

if ($action === 'checkout_worker') {
    $userId = (int) $argv[2];
    $skuId = (int) $argv[3];
    $addressId = (int) $argv[4];

    $user = User::find($userId);
    $address = Address::find($addressId);

    // Put 1 item in cart
    CartItem::updateOrCreate([
        'user_id' => $user->id,
        'sku_id' => $skuId,
    ], ['quantity' => 1]);

    try {
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->createOrder($user, [
            'address_id' => $address->id,
            'delivery_type' => 'pickup',
        ]);
        echo json_encode(['status' => 'SUCCESS', 'order_number' => $order->order_number]);
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'FAILED', 'error' => $e->getMessage()]);
    }
    exit(0);
}

if ($action === 'sequence_worker') {
    $seqKey = $argv[2];
    try {
        $val = SequenceService::next($seqKey);
        echo json_encode(['status' => 'SUCCESS', 'value' => $val]);
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'FAILED', 'error' => $e->getMessage()]);
    }
    exit(0);
}

// MAIN RUNNER
echo "\n==========================================================\n";
echo "1. TESTING TWO TRULY PARALLEL CHECKOUTS FOR LAST UNIT (1 SKU)\n";
echo "==========================================================\n";

// Find or create test users & SKU
$userA = User::firstOrCreate(['email' => 'worker_a@test.com'], [
    'name' => 'Worker A',
    'phone' => '9876500001',
    'password' => bcrypt('password'),
    'account_type' => 'personal',
    'is_active' => true,
]);

$userB = User::firstOrCreate(['email' => 'worker_b@test.com'], [
    'name' => 'Worker B',
    'phone' => '9876500002',
    'password' => bcrypt('password'),
    'account_type' => 'personal',
    'is_active' => true,
]);

$addressA = Address::firstOrCreate(['user_id' => $userA->id, 'label' => 'A HQ'], [
    'contact_name' => 'Worker A',
    'contact_phone' => '9876500001',
    'address_line_1' => 'Maninagar',
    'city' => 'Ahmedabad',
    'state' => 'Gujarat',
    'pincode' => '380008',
    'is_default' => true,
]);

$addressB = Address::firstOrCreate(['user_id' => $userB->id, 'label' => 'B HQ'], [
    'contact_name' => 'Worker B',
    'contact_phone' => '9876500002',
    'address_line_1' => 'CG Road',
    'city' => 'Ahmedabad',
    'state' => 'Gujarat',
    'pincode' => '380009',
    'is_default' => true,
]);

$sku = ProductSku::first();
if (!$sku) {
    echo "ERROR: No SKU found. Seed database first.\n";
    exit(1);
}

// Set stock to exactly 1 unit
$sku->update([
    'stock_quantity' => 1,
    'reserved_quantity' => 0,
]);

echo "Initial SKU state -> Stock: 1, Reserved: 0, Available: 1\n";
echo "Spawning 2 concurrent processes trying to checkout the same last unit...\n";

$phpPath = PHP_BINARY;
$scriptPath = __FILE__;

$cmdA = "\"$phpPath\" \"$scriptPath\" checkout_worker {$userA->id} {$sku->id} {$addressA->id}";
$cmdB = "\"$phpPath\" \"$scriptPath\" checkout_worker {$userB->id} {$sku->id} {$addressB->id}";

$descriptors = [
    0 => ["pipe", "r"],
    1 => ["pipe", "w"],
    2 => ["pipe", "w"],
];

$pA = proc_open($cmdA, $descriptors, $pipesA);
$pB = proc_open($cmdB, $descriptors, $pipesB);

$outA = stream_get_contents($pipesA[1]);
$errA = stream_get_contents($pipesA[2]);
fclose($pipesA[0]); fclose($pipesA[1]); fclose($pipesA[2]);
proc_close($pA);

$outB = stream_get_contents($pipesB[1]);
$errB = stream_get_contents($pipesB[2]);
fclose($pipesB[0]); fclose($pipesB[1]); fclose($pipesB[2]);
proc_close($pB);

echo "Process A Output: $outA\n";
if ($errA) echo "Process A Error: $errA\n";

echo "Process B Output: $outB\n";
if ($errB) echo "Process B Error: $errB\n";

$resA = json_decode($outA, true);
$resB = json_decode($outB, true);

$successCount = 0;
$failCount = 0;

if (($resA['status'] ?? '') === 'SUCCESS') $successCount++; else $failCount++;
if (($resB['status'] ?? '') === 'SUCCESS') $successCount++; else $failCount++;

$freshSku = $sku->fresh();
echo "Final SKU state -> Stock: {$freshSku->stock_quantity}, Reserved: {$freshSku->reserved_quantity}, Available: {$freshSku->available_quantity}\n";

if ($successCount === 1 && $failCount === 1 && $freshSku->reserved_quantity === 1 && $freshSku->available_quantity === 0) {
    echo ">>> RESULT: PASS! Exactly 1 checkout succeeded, 1 was safely blocked with 'Insufficient stock'. 0 oversell occurred.\n\n";
} else {
    echo ">>> RESULT: FAIL! Concurrency race violation detected.\n\n";
    exit(1);
}

echo "==========================================================\n";
echo "2. TESTING 20 PARALLEL PROCESSES GENERATING SEQUENCES\n";
echo "==========================================================\n";

$testKey = 'parallel_stress_' . time();
echo "Spawning 20 parallel processes requesting next sequence for key '$testKey'...\n";

$processes = [];
$pipes = [];

for ($i = 0; $i < 20; $i++) {
    $cmd = "\"$phpPath\" \"$scriptPath\" sequence_worker $testKey";
    $p = proc_open($cmd, $descriptors, $pipes[$i]);
    $processes[$i] = $p;
}

$generatedValues = [];
$errors = [];

for ($i = 0; $i < 20; $i++) {
    $out = stream_get_contents($pipes[$i][1]);
    $err = stream_get_contents($pipes[$i][2]);
    fclose($pipes[$i][0]);
    fclose($pipes[$i][1]);
    fclose($pipes[$i][2]);
    proc_close($processes[$i]);

    $data = json_decode($out, true);
    if (isset($data['value'])) {
        $generatedValues[] = (int) $data['value'];
    } else {
        $errors[] = "Worker $i failed: Output='$out' Error='$err'";
    }
}

if (!empty($errors)) {
    echo "Worker errors encountered:\n" . implode("\n", $errors) . "\n";
}

sort($generatedValues);
echo "Generated Sequence Values (" . count($generatedValues) . " total): " . implode(', ', $generatedValues) . "\n";

$uniqueValues = array_unique($generatedValues);
$expectedValues = range(1, 20);

if (count($uniqueValues) === 20 && $generatedValues === $expectedValues) {
    echo ">>> RESULT: PASS! All 20 numbers strictly sequential (1..20) with 0 collisions and 0 duplicate sequence numbers.\n";
} else {
    echo ">>> RESULT: FAIL! Sequence duplicate or gap detected.\n";
    exit(1);
}
