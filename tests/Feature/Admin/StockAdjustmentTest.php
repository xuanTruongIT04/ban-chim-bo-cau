<?php

use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('Stock Adjustment', function () {
    function makeAdminToken(): string
    {
        $admin = UserModel::factory()->create();

        return $admin->createToken('test')->plainTextToken;
    }

    it('can adjust stock with positive delta', function () {
        $token = makeAdminToken();
        $product = ProductModel::factory()->create(['stock_quantity' => '10.000']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                'delta'           => '50.000',
                'adjustment_type' => 'nhap_hang',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.delta', '50.000')
            ->assertJsonPath('data.stock_after', '60.000');

        expect($product->fresh()->stock_quantity)->toBe('60.000');
    });

    it('can adjust stock with negative delta', function () {
        $token = makeAdminToken();
        $product = ProductModel::factory()->create(['stock_quantity' => '20.000']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                'delta'           => '-5.000',
                'adjustment_type' => 'hu_hong',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.stock_before', '20.000')
            ->assertJsonPath('data.stock_after', '15.000');
    });

    it('rejects adjustment that would make stock negative', function () {
        $token = makeAdminToken();
        $product = ProductModel::factory()->create(['stock_quantity' => '3.000']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                'delta'           => '-5.000',
                'adjustment_type' => 'hu_hong',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');
    });

    it('records stock_before and stock_after', function () {
        $token = makeAdminToken();
        $product = ProductModel::factory()->create(['stock_quantity' => '10.500']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                'delta'           => '2.300',
                'adjustment_type' => 'nhap_hang',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.stock_before', '10.500')
            ->assertJsonPath('data.stock_after', '12.800');
    });

    it('requires adjustment_type', function () {
        $token = makeAdminToken();
        $product = ProductModel::factory()->create();

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
                'delta' => '5.000',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['adjustment_type']]);
    });

    it('requires auth for stock adjustment', function () {
        $product = ProductModel::factory()->create();

        $response = $this->postJson("/api/v1/admin/products/{$product->id}/stock-adjustments", [
            'delta'           => '5.000',
            'adjustment_type' => 'nhap_hang',
        ]);

        $response->assertStatus(401);
    });
});
