<?php

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('Product admin CRUD', function () {
    function makeProductAdminHeaders(): array
    {
        $admin = UserModel::factory()->create();
        $token = $admin->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    function makeProductPayload(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'name'        => 'Chim Bồ Câu Sống',
            'description' => 'Chim bồ câu tươi ngon',
            'price_vnd'   => 80000,
            'unit_type'   => 'con',
            'category_id' => $categoryId,
            'is_active'   => true,
        ], $overrides);
    }

    it('can create a product with unit_type con', function () {
        $category = CategoryModel::factory()->create();

        $response = $this->withHeaders(makeProductAdminHeaders())
            ->postJson('/api/v1/admin/products', makeProductPayload($category->id, ['unit_type' => 'con']));

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unit_type', 'con')
            ->assertJsonPath('data.name', 'Chim Bồ Câu Sống');
    });

    it('can create a product with unit_type kg', function () {
        $category = CategoryModel::factory()->create();

        $response = $this->withHeaders(makeProductAdminHeaders())
            ->postJson('/api/v1/admin/products', makeProductPayload($category->id, [
                'unit_type' => 'kg',
                'name'      => 'Thịt Bồ Câu',
            ]));

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unit_type', 'kg');
    });

    it('can update a product', function () {
        $category = CategoryModel::factory()->create();
        $product  = ProductModel::factory()->create([
            'category_id' => $category->id,
            'unit_type'   => 'con',
        ]);

        $response = $this->withHeaders(makeProductAdminHeaders())
            ->putJson("/api/v1/admin/products/{$product->id}", [
                'name'        => 'Tên Sản Phẩm Mới',
                'description' => 'Mô tả mới',
                'price_vnd'   => 95000,
                'unit_type'   => 'con',
                'category_id' => $category->id,
                'is_active'   => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Tên Sản Phẩm Mới')
            ->assertJsonPath('data.price_vnd', 95000);
    });

    it('can delete a product', function () {
        $category = CategoryModel::factory()->create();
        $product  = ProductModel::factory()->create(['category_id' => $category->id]);

        $response = $this->withHeaders(makeProductAdminHeaders())
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    });

    it('can toggle product is_active', function () {
        $category = CategoryModel::factory()->create();
        $product  = ProductModel::factory()->create([
            'category_id' => $category->id,
            'is_active'   => true,
        ]);

        $headers = makeProductAdminHeaders();

        // First toggle: active → inactive
        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/products/{$product->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        // Second toggle: inactive → active
        $response2 = $this->withHeaders($headers)
            ->patchJson("/api/v1/admin/products/{$product->id}/toggle-active");

        $response2->assertStatus(200)
            ->assertJsonPath('data.is_active', true);
    });

    it('requires auth for product endpoints', function () {
        $response = $this->getJson('/api/v1/admin/products');

        $response->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    });
});
