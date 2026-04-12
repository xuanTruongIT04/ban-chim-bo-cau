<?php

use App\Infrastructure\Persistence\Eloquent\Models\CartItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\CartModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Illuminate\Support\Str;

describe('Checkout', function () {
    function makeCartWithItems(array $productOverrides = [], array $itemQuantities = []): array
    {
        $cart     = CartModel::factory()->create();
        $products = [];

        foreach ($itemQuantities as $i => $qty) {
            $overrides = $productOverrides[$i] ?? [];
            $product   = ProductModel::factory()->create(array_merge([
                'price_vnd'      => 50000,
                'stock_quantity' => '10.000',
                'is_active'      => true,
            ], $overrides));
            CartItemModel::create([
                'cart_id'    => $cart->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
            ]);
            $products[] = $product;
        }

        return [$cart, $products];
    }

    function validCheckoutPayload(array $override = []): array
    {
        return array_merge([
            'customer_name'    => 'Nguyễn Văn A',
            'customer_phone'   => '0901234567',
            'delivery_address' => '123 Đường ABC, TP.HCM',
        ], $override);
    }

    it('places an order from cart, decrements stock atomically', function () {
        [$cart, $products] = makeCartWithItems(
            [['stock_quantity' => '10.000'], ['stock_quantity' => '10.000']],
            ['3', '2'],
        );

        $idempotencyKey = (string) Str::uuid();

        $response = $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/v1/checkout', validCheckoutPayload());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_status', 'cho_xac_nhan')
            ->assertJsonPath('data.payment_status', 'chua_thanh_toan');

        expect($response->json('data.items'))->toHaveCount(2);

        $this->assertDatabaseHas('products', [
            'id'             => $products[0]->id,
            'stock_quantity' => '7.000',
        ]);
        $this->assertDatabaseHas('products', [
            'id'             => $products[1]->id,
            'stock_quantity' => '8.000',
        ]);
    });

    it('returns same order for duplicate idempotency key', function () {
        [$cart, $products] = makeCartWithItems(
            [['stock_quantity' => '10.000']],
            ['2'],
        );

        $idempotencyKey = (string) Str::uuid();

        $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/v1/checkout', validCheckoutPayload());

        // Second request with same idempotency key
        // Cart is deleted after first order; use a new cart + same key
        [$cart2, $products2] = makeCartWithItems(
            [['stock_quantity' => '10.000']],
            ['2'],
        );

        $response = $this->withHeaders([
            'X-Cart-Token'   => $cart2->token,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/v1/checkout', validCheckoutPayload());

        // Same idempotency key returns cached response — only 1 order
        $this->assertDatabaseCount('orders', 1);
    });

    it('rejects checkout when stock is insufficient', function () {
        [$cart, $products] = makeCartWithItems(
            [['stock_quantity' => '2.000']],
            ['5'],
        );

        $response = $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', validCheckoutPayload());

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');
    });

    it('rejects checkout with inactive product in cart', function () {
        [$cart, $products] = makeCartWithItems(
            [['is_active' => false, 'stock_quantity' => '10.000']],
            ['2'],
        );

        $response = $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', validCheckoutPayload());

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INACTIVE_PRODUCT_IN_CART');
    });

    it('validates checkout fields — phone must be VN 10-digit', function () {
        [$cart] = makeCartWithItems(
            [['stock_quantity' => '10.000']],
            ['1'],
        );

        $response = $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', validCheckoutPayload([
            'customer_phone' => '12345',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['customer_phone']]);
    });

    it('validates checkout fields — all required', function () {
        [$cart] = makeCartWithItems(
            [['stock_quantity' => '10.000']],
            ['1'],
        );

        $response = $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => [
                'customer_name',
                'customer_phone',
                'delivery_address',
                'payment_method',
            ]]);
    });

    it('starts COD order with payment_status chua_thanh_toan', function () {
        [$cart] = makeCartWithItems(
            [['stock_quantity' => '10.000']],
            ['1'],
        );

        $response = $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', validCheckoutPayload([
            'payment_method' => 'cod',
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.payment_status', 'chua_thanh_toan')
            ->assertJsonPath('data.payment_method', 'cod');
    });

    it('deletes cart after successful checkout', function () {
        [$cart] = makeCartWithItems(
            [['stock_quantity' => '10.000']],
            ['1'],
        );

        $cartToken = $cart->token;

        $this->withHeaders([
            'X-Cart-Token'   => $cartToken,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', validCheckoutPayload());

        // Cart should be deleted; trying to get it should return 404
        $response = $this->withHeaders(['X-Cart-Token' => $cartToken])
            ->getJson('/api/v1/cart');

        $response->assertStatus(404);
    });

    it('snapshots product name and price at order time', function () {
        $originalName  = 'Chim bồ câu sống';
        $originalPrice = 150000;

        [$cart, $products] = makeCartWithItems(
            [['name' => $originalName, 'price_vnd' => $originalPrice, 'stock_quantity' => '10.000']],
            ['2'],
        );

        $this->withHeaders([
            'X-Cart-Token'   => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', validCheckoutPayload());

        // Change product name and price after order
        $products[0]->update(['name' => 'Tên khác', 'price_vnd' => 999999]);

        // Order item should still have original name and price
        $this->assertDatabaseHas('order_items', [
            'product_name' => $originalName,
            'price_vnd'    => $originalPrice,
        ]);
    });
});
