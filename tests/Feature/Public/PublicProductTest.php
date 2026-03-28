<?php

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;

describe('Public product API', function () {
    it('can list active products without auth', function () {
        $category = CategoryModel::factory()->create();
        ProductModel::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    });

    it('does not list inactive products', function () {
        $category = CategoryModel::factory()->create();
        ProductModel::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        ProductModel::factory()->create(['category_id' => $category->id, 'is_active' => false]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    });

    it('can filter products by category_id', function () {
        $cat1 = CategoryModel::factory()->create();
        $cat2 = CategoryModel::factory()->create();

        ProductModel::factory()->count(2)->create(['category_id' => $cat1->id, 'is_active' => true]);
        ProductModel::factory()->count(3)->create(['category_id' => $cat2->id, 'is_active' => true]);

        $response = $this->getJson("/api/v1/products?filter[category_id]={$cat1->id}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        foreach ($response->json('data') as $item) {
            $this->assertEquals($cat1->id, $item['category_id']);
        }
    });

    it('can view product detail', function () {
        $category = CategoryModel::factory()->create();
        $product  = ProductModel::factory()->create([
            'category_id' => $category->id,
            'is_active'   => true,
            'name'        => 'Chim Sống',
            'description' => 'Mô tả chi tiết',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Chim Sống')
            ->assertJsonStructure(['data' => ['category', 'stock_quantity']]);
    });

    it('shows stock quantity in public response', function () {
        $category = CategoryModel::factory()->create();
        $product  = ProductModel::factory()->create([
            'category_id'    => $category->id,
            'is_active'      => true,
            'stock_quantity' => '5.500',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertEquals('5.500', $response->json('data.stock_quantity'));
    });

    it('returns 404 for inactive product detail', function () {
        $category = CategoryModel::factory()->create();
        $product  = ProductModel::factory()->create([
            'category_id' => $category->id,
            'is_active'   => false,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(404)
            ->assertJsonPath('code', 'PRODUCT_NOT_FOUND');
    });

    it('paginates with per_page parameter', function () {
        $category = CategoryModel::factory()->create();
        ProductModel::factory()->count(25)->create(['category_id' => $category->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertArrayHasKey('meta', $response->json());
    });
});
