<?php

use App\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Product Image Management', function () {
    beforeEach(function () {
        Storage::fake('s3');
    });

    function makeAdminUser(): UserModel
    {
        return UserModel::factory()->create();
    }

    it('can upload an image to S3', function () {
        $admin = makeAdminUser();
        $product = ProductModel::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image'      => UploadedFile::fake()->image('photo.jpg', 800, 600),
                'is_primary' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'url', 'thumbnail_url', 'is_primary', 'sort_order']]);

        // Verify files were stored in S3
        $imageRecord = ProductImageModel::where('product_id', $product->id)->first();
        expect($imageRecord)->not->toBeNull();
        Storage::disk('s3')->assertExists($imageRecord->path);
        Storage::disk('s3')->assertExists($imageRecord->thumbnail_path);
    });

    it('generates thumbnail on upload', function () {
        $admin = makeAdminUser();
        $product = ProductModel::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
            ]);

        $response->assertStatus(201);

        $imageRecord = ProductImageModel::where('product_id', $product->id)->first();
        expect($imageRecord)->not->toBeNull();

        // Thumbnail path includes thumb_ prefix
        expect($imageRecord->thumbnail_path)->toContain('thumb_');

        // Thumbnail URL is different from original URL
        $data = $response->json('data');
        expect($data['thumbnail_url'])->not->toBeNull();
        expect($data['thumbnail_url'])->not->toBe($data['url']);

        Storage::disk('s3')->assertExists($imageRecord->thumbnail_path);
    });

    it('auto-sets first image as primary', function () {
        $admin = makeAdminUser();
        $product = ProductModel::factory()->create();

        // Ensure no images exist
        expect(ProductImageModel::where('product_id', $product->id)->count())->toBe(0);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image'      => UploadedFile::fake()->image('first.jpg', 400, 300),
                'is_primary' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_primary', true);
    });

    it('can set primary image', function () {
        $admin = makeAdminUser();
        $product = ProductModel::factory()->create();

        // Upload first image (auto-primary)
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image' => UploadedFile::fake()->image('first.jpg', 400, 300),
            ]);

        // Upload second image (not primary)
        $secondResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image'      => UploadedFile::fake()->image('second.jpg', 400, 300),
                'is_primary' => false,
            ]);

        $secondImageId = $secondResponse->json('data.id');

        // Set second image as primary
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/products/{$product->id}/images/{$secondImageId}/primary");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $secondImageId)
            ->assertJsonPath('data.is_primary', true);

        // Verify first image is no longer primary
        $firstImage = ProductImageModel::where('product_id', $product->id)
            ->where('id', '!=', $secondImageId)
            ->first();
        expect($firstImage->is_primary)->toBeFalse();
    });

    it('can delete an image', function () {
        $admin = makeAdminUser();
        $product = ProductModel::factory()->create();

        $uploadResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image' => UploadedFile::fake()->image('photo.jpg', 400, 300),
            ]);

        $imageId = $uploadResponse->json('data.id');
        $imageRecord = ProductImageModel::find($imageId);
        $originalPath = $imageRecord->path;
        $thumbnailPath = $imageRecord->thumbnail_path;

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/products/{$product->id}/images/{$imageId}");

        $response->assertStatus(204);

        // Verify DB record deleted
        expect(ProductImageModel::find($imageId))->toBeNull();

        // Verify S3 files deleted
        Storage::disk('s3')->assertMissing($originalPath);
        Storage::disk('s3')->assertMissing($thumbnailPath);
    });

    it('promotes next image when primary is deleted', function () {
        $admin = makeAdminUser();
        $product = ProductModel::factory()->create();

        // Upload first image (auto-primary)
        $firstResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image' => UploadedFile::fake()->image('first.jpg', 400, 300),
            ]);

        $firstImageId = $firstResponse->json('data.id');

        // Upload second image
        $secondResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/products/{$product->id}/images", [
                'image'      => UploadedFile::fake()->image('second.jpg', 400, 300),
                'is_primary' => false,
            ]);

        $secondImageId = $secondResponse->json('data.id');

        // Verify first is primary
        expect(ProductImageModel::find($firstImageId)->is_primary)->toBeTrue();
        expect(ProductImageModel::find($secondImageId)->is_primary)->toBeFalse();

        // Delete the primary (first) image
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/products/{$product->id}/images/{$firstImageId}")
            ->assertStatus(204);

        // Second image should now be primary
        expect(ProductImageModel::find($secondImageId)->is_primary)->toBeTrue();
    });

    it('requires auth for image endpoints', function () {
        $product = ProductModel::factory()->create();

        $this->postJson("/api/v1/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertStatus(401);
    });
});
