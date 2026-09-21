<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\RazorpayService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EcommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected ProductSku $sku;
    protected Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base fixtures
        $this->user = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '9876543210',
            'password' => bcrypt('password123'),
            'account_type' => 'personal',
            'is_active' => true,
        ]);

        $this->otherUser = User::create([
            'name' => 'Other Customer',
            'email' => 'other@test.com',
            'phone' => '9876543211',
            'password' => bcrypt('password123'),
            'account_type' => 'personal',
            'is_active' => true,
        ]);

        $this->address = $this->user->addresses()->create([
            'label' => 'Home',
            'contact_name' => 'Test Customer',
            'contact_phone' => '9876543210',
            'address_line_1' => '101, Maninagar Cross Road',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380008',
            'is_default' => true,
        ]);

        $category = Category::create([
            'name' => 'RAM',
            'slug' => 'ram',
            'is_active' => true,
        ]);

        $brand = Brand::create([
            'name' => 'Crucial',
            'slug' => 'crucial',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Crucial 16GB DDR4 3200MHz RAM',
            'slug' => 'crucial-16gb-ddr4-3200mhz',
            'hsn_code' => '84733020',
            'gst_rate' => 18.00,
            'is_active' => true,
        ]);

        $this->sku = ProductSku::create([
            'product_id' => $product->id,
            'sku' => 'CRU-DDR4-16G',
            'variant_label' => '16GB DDR4',
            'cost_price' => 2500.00,
            'retail_price' => 3800.00,
            'selling_price' => 3200.00,
            'stock_quantity' => 10,
            'reserved_quantity' => 0,
            'min_stock_alert' => 2,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Add to cart (both guest and authenticated).
     */
    public function test_add_to_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/cart', [
                'sku_id' => $this->sku->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.sku_id', $this->sku->id)
            ->assertJsonPath('data.quantity', 2);
    }

    /**
     * Test 1b: Guest cart requires valid UUID and rejects guessable session IDs.
     */
    public function test_guest_cart_uuid_validation(): void
    {
        // 1. Guest user with valid UUID adds item
        $guestUuid = (string) Str::uuid();
        $guestResponse = $this->withHeader('X-Session-Id', $guestUuid)
            ->postJson('/api/v1/cart', [
                'sku_id' => $this->sku->id,
                'quantity' => 1,
            ]);

        $guestResponse->assertStatus(201);

        // 2. Guest user with invalid UUID is rejected (Security check 6)
        $invalidGuestResponse = $this->withHeader('X-Session-Id', 'invalid-guessable-id-123')
            ->postJson('/api/v1/cart', [
                'sku_id' => $this->sku->id,
                'quantity' => 1,
            ]);

        $invalidGuestResponse->assertStatus(422);
    }

    /**
     * Test 2: Checkout creating an order with stock reserved.
     */
    public function test_checkout_creates_order_and_reserves_stock(): void
    {
        // Add to cart
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart', [
                'sku_id' => $this->sku->id,
                'quantity' => 3,
            ]);

        $initialStock = $this->sku->fresh()->stock_quantity;
        $initialReserved = $this->sku->fresh()->reserved_quantity;

        // Perform checkout
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
                'delivery_type' => 'standard',
            ]);

        $response->assertStatus(201);
        $orderNumber = $response->json('data.order_number');
        $this->assertNotEmpty($orderNumber);

        // Verify order in database
        $order = Order::where('order_number', $orderNumber)->first();
        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::NEW, $order->status);

        // Verify stock is reserved (reserved_quantity increased by 3, stock_quantity remains 10)
        $skuFresh = $this->sku->fresh();
        $this->assertEquals($initialStock, $skuFresh->stock_quantity);
        $this->assertEquals($initialReserved + 3, $skuFresh->reserved_quantity);
        $this->assertEquals(7, $skuFresh->available_quantity);
    }

    /**
     * Test 3: Payment confirmation via webhook (including duplicate webhook).
     */
    public function test_payment_confirmation_via_webhook_and_duplicate_handling(): void
    {
        // Add to cart
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart', [
                'sku_id' => $this->sku->id,
                'quantity' => 2,
            ]);

        $checkoutResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
                'delivery_type' => 'pickup',
            ]);

        $checkoutResponse->assertStatus(201);
        $orderNumber = $checkoutResponse->json('data.order_number');
        $order = Order::where('order_number', $orderNumber)->first();

        // Step 2: Payment order initiated on Razorpay (Order transitions NEW -> PAYMENT_PENDING)
        $order->transitionTo(OrderStatus::PAYMENT_PENDING, 'Payment order initiated');
        $razorpayOrderId = 'order_test_' . Str::random(10);
        $payment = Payment::create([
            'order_id' => $order->id,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $order->total_amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
        ]);

        // Construct captured webhook payload
        $razorpayPaymentId = 'pay_test_' . Str::random(10);
        $amountInPaise = (int) round($order->total_amount * 100);

        $payload = json_encode([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $razorpayPaymentId,
                        'order_id' => $razorpayOrderId,
                        'amount' => $amountInPaise,
                        'currency' => 'INR',
                        'method' => 'upi',
                    ],
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret');

        // First delivery of webhook
        $response1 = $this->call(
            'POST',
            '/api/v1/payments/webhook',
            [],
            [],
            [],
            [
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response1->assertStatus(200);

        // Verify order is now marked PAID and stock is committed
        $orderFresh = $order->fresh();
        $this->assertEquals(OrderStatus::PAID, $orderFresh->status);
        $this->assertEquals(PaymentStatus::CAPTURED, $payment->fresh()->status);
        $this->assertEquals(8, $this->sku->fresh()->stock_quantity);
        $this->assertEquals(0, $this->sku->fresh()->reserved_quantity);

        // Second duplicate delivery of the exact same webhook
        $response2 = $this->call(
            'POST',
            '/api/v1/payments/webhook',
            [],
            [],
            [],
            [
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        // Must succeed with status 200 without double-decrementing stock
        $response2->assertStatus(200);
        $this->assertEquals(8, $this->sku->fresh()->stock_quantity);
        $this->assertEquals(0, $this->sku->fresh()->reserved_quantity);
    }

    /**
     * Test 4: Cancellation releasing stock.
     */
    public function test_cancellation_releases_stock(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart', [
                'sku_id' => $this->sku->id,
                'quantity' => 4,
            ]);

        $checkoutResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
                'delivery_type' => 'standard',
            ]);

        $orderNumber = $checkoutResponse->json('data.order_number');
        $this->assertEquals(4, $this->sku->fresh()->reserved_quantity);

        // Cancel the order
        $cancelResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/orders/{$orderNumber}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $cancelResponse->assertStatus(200);

        // Verify order status is CANCELLED
        $order = Order::where('order_number', $orderNumber)->first();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        // Verify reserved stock was released back to 0
        $this->assertEquals(0, $this->sku->fresh()->reserved_quantity);
        $this->assertEquals(10, $this->sku->fresh()->available_quantity);
    }

    /**
     * Test 5: A user being blocked from another user's order.
     */
    public function test_user_is_blocked_from_another_users_order(): void
    {
        // User 1 creates an order
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart', [
                'sku_id' => $this->sku->id,
                'quantity' => 1,
            ]);

        $checkoutResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
                'delivery_type' => 'pickup',
            ]);

        $orderNumber = $checkoutResponse->json('data.order_number');

        // Other user attempts to view User 1's order
        $viewResponse = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/orders/{$orderNumber}");

        $viewResponse->assertStatus(404);

        // Other user attempts to cancel User 1's order
        $cancelResponse = $this->actingAs($this->otherUser)
            ->postJson("/api/v1/orders/{$orderNumber}/cancel", [
                'reason' => 'Malicious cancel attempt',
            ]);

        $cancelResponse->assertStatus(404);
    }

    /**
     * Test 6: Two concurrent orders for the last unit of stock.
     */
    public function test_two_concurrent_orders_for_last_unit_of_stock(): void
    {
        // Set SKU stock to exactly 1 unit
        $this->sku->update([
            'stock_quantity' => 1,
            'reserved_quantity' => 0,
        ]);

        // Setup addresses
        $otherAddress = $this->otherUser->addresses()->create([
            'label' => 'Office',
            'contact_name' => 'Other Customer',
            'contact_phone' => '9876543211',
            'address_line_1' => '202, CG Road',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380009',
            'is_default' => true,
        ]);

        // Add 1 unit to User 1's cart
        $this->actingAs($this->user)->postJson('/api/v1/cart', [
            'sku_id' => $this->sku->id,
            'quantity' => 1,
        ]);

        // Add 1 unit to User 2's cart
        $this->actingAs($this->otherUser)->postJson('/api/v1/cart', [
            'sku_id' => $this->sku->id,
            'quantity' => 1,
        ]);

        // Simulate concurrent execution where User 1 completes checkout first
        $order1Response = $this->actingAs($this->user)
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
                'delivery_type' => 'pickup',
            ]);

        $order1Response->assertStatus(201);

        // User 2 immediately attempts checkout for the same SKU
        $order2Response = $this->actingAs($this->otherUser)
            ->postJson('/api/v1/checkout', [
                'address_id' => $otherAddress->id,
                'delivery_type' => 'pickup',
            ]);

        // User 2 must be rejected with an error indicating insufficient stock
        $this->assertTrue(in_array($order2Response->status(), [422, 500]));
        $this->assertStringContainsString('Insufficient stock', $order2Response->getContent());

        // Verify only 1 unit was reserved and stock was never negative
        $this->assertEquals(1, $this->sku->fresh()->stock_quantity);
        $this->assertEquals(1, $this->sku->fresh()->reserved_quantity);
        $this->assertEquals(0, $this->sku->fresh()->available_quantity);
    }
}
