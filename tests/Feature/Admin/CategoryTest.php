<?php

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('Category admin CRUD', function () {
    function makeAdminHeaders(): array
    {
        $admin = UserModel::factory()->create();
        $token = $admin->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    it('can create a root category', function () {
        $response = $this->withHeaders(makeAdminHeaders())
            ->postJson('/api/v1/admin/categories', [
                'name'      => 'Chim Bồ Câu',
                'slug'      => 'chim-bo-cau',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Chim Bồ Câu')
            ->assertJsonPath('data.slug', 'chim-bo-cau')
            ->assertJsonPath('data.parent_id', null);
    });

    it('can create a child category', function () {
        $parent = CategoryModel::factory()->create(['slug' => 'gia-cam']);

        $response = $this->withHeaders(makeAdminHeaders())
            ->postJson('/api/v1/admin/categories', [
                'name'      => 'Chim Sống',
                'slug'      => 'chim-song',
                'parent_id' => $parent->id,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parent_id', $parent->id);
    });

    it('auto-generates slug from name when slug not provided on create', function () {
        $response = $this->withHeaders(makeAdminHeaders())
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Chim Bo Cau Song',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Chim Bo Cau Song')
            ->assertJsonPath('data.slug', 'chim-bo-cau-song');
    });

    it('auto-generates slug from name when slug not provided on update', function () {
        $category = CategoryModel::factory()->create(['slug' => 'slug-cu']);

        $response = $this->withHeaders(makeAdminHeaders())
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'name' => 'Ten Moi Cap Nhat',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Ten Moi Cap Nhat')
            ->assertJsonPath('data.slug', 'ten-moi-cap-nhat');
    });

    it('rejects creating a grandchild category (depth > 2)', function () {
        $grandparent = CategoryModel::factory()->create(['slug' => 'level-1']);
        $parent = CategoryModel::factory()->create([
            'slug'      => 'level-2',
            'parent_id' => $grandparent->id,
        ]);

        $response = $this->withHeaders(makeAdminHeaders())
            ->postJson('/api/v1/admin/categories', [
                'name'      => 'Cấp 3',
                'slug'      => 'level-3',
                'parent_id' => $parent->id,
                'is_active' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CATEGORY_DEPTH_EXCEEDED');
    });

    it('can update a category', function () {
        $category = CategoryModel::factory()->create(['slug' => 'old-slug']);

        $response = $this->withHeaders(makeAdminHeaders())
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'name'      => 'Tên Mới',
                'slug'      => 'ten-moi',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Tên Mới')
            ->assertJsonPath('data.slug', 'ten-moi')
            ->assertJsonPath('data.is_active', false);
    });

    it('can delete an empty category', function () {
        $category = CategoryModel::factory()->create();

        $response = $this->withHeaders(makeAdminHeaders())
            ->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('rejects deleting category with products', function () {
        $category = CategoryModel::factory()
            ->hasProducts(1)
            ->create();

        $response = $this->withHeaders(makeAdminHeaders())
            ->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    });

    it('rejects deleting category with children', function () {
        $parent = CategoryModel::factory()->create(['slug' => 'parent-cat']);
        CategoryModel::factory()->create([
            'slug'      => 'child-cat',
            'parent_id' => $parent->id,
        ]);

        $response = $this->withHeaders(makeAdminHeaders())
            ->deleteJson("/api/v1/admin/categories/{$parent->id}");

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    });

    it('requires auth for category endpoints', function () {
        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Test',
            'slug' => 'test',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    });
});
