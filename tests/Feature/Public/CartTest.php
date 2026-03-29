<?php

use App\Infrastructure\Persistence\Eloquent\Models\CartModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;

describe('Cart API', function () {

    // -------------------------------------------------------------------------
    // Cart creation (CART-01)
    // -------------------------------------------------------------------------

    it('creates a new cart and returns UUID token', function () {
        $response = $this->postJson('/api/v1/cart');

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'expires_at']]);

        $token = $response->json('data.token');
        expect($token)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    it('returns 401 when X-Cart-Token header is missing on cart endpoints', function () {
        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CART_TOKEN_REQUIRED');
    });

    it('returns 404 when X-Cart-Token is invalid', function () {
        $response = $this->withHeaders(['X-Cart-Token' => 'invalid-token-xyz'])
            ->getJson('/api/v1/cart');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CART_NOT_FOUND');
    });

    // -------------------------------------------------------------------------
    // Add to cart (CART-01, CART-04)
    // -------------------------------------------------------------------------

    it('adds a product to cart', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $product = ProductModel::factory()->create([
            'stock_quantity' => '10.000',
            'is_active'      => true,
        ]);

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'token', 'expires_at', 'items', 'total_amount']]);

        expect($response->json('data.items'))->toHaveCount(1);
        expect($response->json('data.items.0.product_id'))->toBe($product->id);
        expect($response->json('data.items.0.quantity'))->toBe('3.000');
    });

    it('accumulates quantity when adding same product twice', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $product = ProductModel::factory()->create(['is_active' => true]);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 3,
            ]);

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 2,
            ]);

        $response->assertStatus(201);
        expect($response->json('data.items'))->toHaveCount(1);
        expect($response->json('data.items.0.quantity'))->toBe('5.000');
    });

    it('rejects adding inactive product', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $product = ProductModel::factory()->create(['is_active' => false]);

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INACTIVE_PRODUCT_IN_CART');
    });

    it('does NOT decrement stock when adding to cart', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $product = ProductModel::factory()->create([
            'stock_quantity' => '10.000',
            'is_active'      => true,
        ]);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 3,
            ]);

        // CART-04: stock must remain unchanged
        $this->assertDatabaseHas('products', [
            'id'             => $product->id,
            'stock_quantity' => '10.000',
        ]);
    });

    // -------------------------------------------------------------------------
    // View cart (CART-02)
    // -------------------------------------------------------------------------

    it('returns empty items for new cart', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total_amount', 0);
    });

    it('returns cart with items, current prices, and total', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $productA = ProductModel::factory()->create(['price_vnd' => 50000, 'is_active' => true]);
        $productB = ProductModel::factory()->create(['price_vnd' => 30000, 'is_active' => true]);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $productA->id,
                'quantity'   => 2,
            ]);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $productB->id,
                'quantity'   => 1,
            ]);

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data.items');
        expect($items)->toHaveCount(2);

        $itemA = collect($items)->firstWhere('product_id', $productA->id);
        expect($itemA['product_name'])->toBe($productA->name);
        expect($itemA['product_price_vnd'])->toBe(50000);
        expect($itemA['quantity'])->toBe('2.000');
        expect($itemA['subtotal'])->toBe(100000); // 2 * 50000
        expect($itemA['is_available'])->toBeTrue();

        // total = 2 * 50000 + 1 * 30000 = 130000
        expect($response->json('data.total_amount'))->toBe(130000);
    });

    // -------------------------------------------------------------------------
    // Update / Remove (CART-03)
    // -------------------------------------------------------------------------

    it('updates item quantity', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $product = ProductModel::factory()->create(['is_active' => true]);

        $addResponse = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 2,
            ]);

        $itemId = $addResponse->json('data.items.0.id');

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 7]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        expect($response->json('data.items.0.quantity'))->toBe('7.000');
    });

    it('removes item from cart', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $product = ProductModel::factory()->create(['is_active' => true]);

        $addResponse = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 1,
            ]);

        $itemId = $addResponse->json('data.items.0.id');

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->deleteJson("/api/v1/cart/items/{$itemId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Đã xóa sản phẩm khỏi giỏ hàng.');

        // Verify item is gone
        $cartNow = $this->withHeaders(['X-Cart-Token' => $token])
            ->getJson('/api/v1/cart');

        expect($cartNow->json('data.items'))->toHaveCount(0);
    });

    it('rejects quantity of 0 or negative', function () {
        $cartResponse = $this->postJson('/api/v1/cart');
        $token        = $cartResponse->json('data.token');

        $product = ProductModel::factory()->create(['is_active' => true]);

        $addResponse = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 1,
            ]);

        $itemId = $addResponse->json('data.items.0.id');

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 0]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['quantity']]);
    });
});
