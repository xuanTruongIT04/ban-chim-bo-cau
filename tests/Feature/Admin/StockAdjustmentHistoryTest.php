<?php

use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('Stock Adjustment History', function () {
    function makeAdminTokenForHistory(): string
    {
        $admin = UserModel::factory()->create();

        return $admin->createToken('test')->plainTextToken;
    }

    it('can list stock adjustments for a product', function () {
        $token = makeAdminTokenForHistory();
        $product = ProductModel::factory()->create(['stock_quantity' => '100.000']);

        // Make 3 adjustments
        for ($i = 0; $i < 3; $i++) {
            $this->withToken($token)
                ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                    'delta'           => '1.000',
                    'adjustment_type' => 'nhap_hang',
                ]);
        }

        $response = $this->withToken($token)
            ->getJson("/api/v1/admin/products/{$product->id}/stock-adjustments");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['delta', 'adjustment_type', 'stock_before', 'stock_after', 'created_at'],
                ],
            ]);
    });

    it('paginates stock adjustment history', function () {
        $token = makeAdminTokenForHistory();
        $product = ProductModel::factory()->create(['stock_quantity' => '1000.000']);

        // Make 20 adjustments
        for ($i = 0; $i < 20; $i++) {
            $this->withToken($token)
                ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                    'delta'           => '1.000',
                    'adjustment_type' => 'nhap_hang',
                ]);
        }

        $response = $this->withToken($token)
            ->getJson("/api/v1/admin/products/{$product->id}/stock-adjustments?per_page=5");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20);
    });

    it('shows newest adjustments first', function () {
        $token = makeAdminTokenForHistory();
        $product = ProductModel::factory()->create(['stock_quantity' => '100.000']);

        // Make 3 adjustments sequentially
        for ($i = 0; $i < 3; $i++) {
            $this->withToken($token)
                ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                    'delta'           => '1.000',
                    'adjustment_type' => 'nhap_hang',
                ]);
        }

        $response = $this->withToken($token)
            ->getJson("/api/v1/admin/products/{$product->id}/stock-adjustments");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(3);

        // Verify newest first: each entry's created_at should be >= the next
        expect($data[0]['created_at'] >= $data[1]['created_at'])->toBeTrue();
        expect($data[1]['created_at'] >= $data[2]['created_at'])->toBeTrue();
    });

    it('requires auth for stock adjustment history', function () {
        $product = ProductModel::factory()->create();

        $response = $this->getJson("/api/v1/admin/products/{$product->id}/stock-adjustments");

        $response->assertStatus(401);
    });
});
