<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding categories...');
        $categories = $this->seedCategories();

        $this->command->info('🌱 Seeding products...');
        $this->seedProducts($categories);

        $this->command->info('✅ Done! Categories and products seeded successfully.');
    }

    /** @return array<string, int> map of slug => id */
    private function seedCategories(): array
    {
        $data = [
            // Parent categories
            ['name' => 'Chim Bồ Câu',          'slug' => 'chim-bo-cau',          'parent' => null, 'sort' => 1],
            ['name' => 'Gia Cầm',               'slug' => 'gia-cam',               'parent' => null, 'sort' => 2],
            ['name' => 'Thức Ăn & Phụ Kiện',   'slug' => 'thuc-an-phu-kien',      'parent' => null, 'sort' => 3],

            // Chim Bồ Câu children
            ['name' => 'Bồ Câu Sống',           'slug' => 'bo-cau-song',           'parent' => 'chim-bo-cau',     'sort' => 1],
            ['name' => 'Bồ Câu Thịt (Làm Sẵn)', 'slug' => 'bo-cau-thit',           'parent' => 'chim-bo-cau',     'sort' => 2],
            ['name' => 'Bồ Câu Con (Squab)',     'slug' => 'bo-cau-con',            'parent' => 'chim-bo-cau',     'sort' => 3],

            // Gia Cầm children
            ['name' => 'Gà',                     'slug' => 'ga',                    'parent' => 'gia-cam',         'sort' => 1],
            ['name' => 'Vịt',                    'slug' => 'vit',                   'parent' => 'gia-cam',         'sort' => 2],
            ['name' => 'Chim Cút',               'slug' => 'chim-cut',              'parent' => 'gia-cam',         'sort' => 3],

            // Thức Ăn children
            ['name' => 'Thức Ăn Chim',          'slug' => 'thuc-an-chim',          'parent' => 'thuc-an-phu-kien','sort' => 1],
            ['name' => 'Phụ Kiện Nuôi Chim',    'slug' => 'phu-kien-nuoi-chim',    'parent' => 'thuc-an-phu-kien','sort' => 2],
        ];

        $slugToId = [];

        // Insert parents first
        foreach ($data as $row) {
            if ($row['parent'] !== null) {
                continue;
            }
            $cat = CategoryModel::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name'       => $row['name'],
                    'parent_id'  => null,
                    'sort_order' => $row['sort'],
                    'is_active'  => true,
                ]
            );
            $slugToId[$row['slug']] = $cat->id;
        }

        // Insert children
        foreach ($data as $row) {
            if ($row['parent'] === null) {
                continue;
            }
            $cat = CategoryModel::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name'       => $row['name'],
                    'parent_id'  => $slugToId[$row['parent']],
                    'sort_order' => $row['sort'],
                    'is_active'  => true,
                ]
            );
            $slugToId[$row['slug']] = $cat->id;
        }

        $this->command->info('   Created ' . count($slugToId) . ' categories');
        return $slugToId;
    }

    /** @param array<string, int> $categories */
    private function seedProducts(array $categories): void
    {
        $products = [
            // ── Bồ Câu Sống ──────────────────────────────────────────
            [
                'category' => 'bo-cau-song',
                'name'     => 'Bồ Câu Pháp Sống (1 con)',
                'desc'     => 'Bồ câu Pháp thuần chủng, trọng lượng 500–600g/con. Nuôi sạch, không hormone. Thích hợp làm thực phẩm hoặc nuôi sinh sản.',
                'price'    => 85000,
                'unit'     => 'con',
                'stock'    => 120,
                'images'   => ['https://images.unsplash.com/photo-1548681528-6a5c45dbe38f?w=1200', 'https://images.unsplash.com/photo-1444464666168-49d633b86797?w=1200'],
            ],
            [
                'category' => 'bo-cau-song',
                'name'     => 'Bồ Câu Ta Sống (1 con)',
                'desc'     => 'Bồ câu ta nuôi tự nhiên, thịt ngọt chắc. Trọng lượng 350–450g/con. Thích hợp nấu cháo, hầm thuốc bắc.',
                'price'    => 65000,
                'unit'     => 'con',
                'stock'    => 80,
                'images'   => ['https://images.unsplash.com/photo-1444464666168-49d633b86797?w=1200'],
            ],
            [
                'category' => 'bo-cau-song',
                'name'     => 'Bồ Câu Pháp Sống (Cặp đôi)',
                'desc'     => 'Combo 2 con bồ câu Pháp (1 trống + 1 mái). Giá ưu đãi khi mua cặp. Thích hợp cho khách muốn mua số lượng lớn.',
                'price'    => 160000,
                'unit'     => 'con',
                'stock'    => 40,
                'images'   => ['https://images.unsplash.com/photo-1548681528-6a5c45dbe38f?w=1200'],
            ],

            // ── Bồ Câu Thịt ──────────────────────────────────────────
            [
                'category' => 'bo-cau-thit',
                'name'     => 'Bồ Câu Pháp Làm Sẵn (1 con)',
                'desc'     => 'Bồ câu Pháp đã làm sạch, bỏ lông, bỏ nội tạng. Đóng túi hút chân không. Trọng lượng tịnh 400–480g/con. Bảo quản ngăn đá được 3 tháng.',
                'price'    => 95000,
                'unit'     => 'con',
                'stock'    => 60,
                'images'   => ['https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=1200', 'https://images.unsplash.com/photo-1548681528-6a5c45dbe38f?w=1200'],
            ],
            [
                'category' => 'bo-cau-thit',
                'name'     => 'Bồ Câu Thịt Nguyên Con (1kg)',
                'desc'     => 'Bồ câu thịt tươi bán theo kg. Đã làm sạch, không đầu, không chân. Thích hợp cho nhà hàng, quán ăn.',
                'price'    => 220000,
                'unit'     => 'kg',
                'stock'    => 15.5,
                'images'   => ['https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=1200'],
            ],

            // ── Bồ Câu Con (Squab) ────────────────────────────────────
            [
                'category' => 'bo-cau-con',
                'name'     => 'Squab Bồ Câu Non 28 Ngày (1 con)',
                'desc'     => 'Squab bồ câu non 28 ngày tuổi, trọng lượng 280–320g/con. Thịt mềm, thơm, giàu dinh dưỡng. Món đặc sản cao cấp cho nhà hàng.',
                'price'    => 120000,
                'unit'     => 'con',
                'stock'    => 30,
                'images'   => ['https://images.unsplash.com/photo-1444464666168-49d633b86797?w=1200'],
            ],

            // ── Gà ────────────────────────────────────────────────────
            [
                'category' => 'ga',
                'name'     => 'Gà Ta Thả Vườn Sống (1 con)',
                'desc'     => 'Gà ta nuôi thả vườn, ăn thóc và rau. Trọng lượng 1.5–2kg/con. Thịt dai ngon, không thuốc tăng trưởng.',
                'price'    => 180000,
                'unit'     => 'con',
                'stock'    => 25,
                'images'   => ['https://images.unsplash.com/photo-1612170153139-6f881ff067e0?w=1200', 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=1200'],
            ],
            [
                'category' => 'ga',
                'name'     => 'Gà Mái Đẻ Sống (1 con)',
                'desc'     => 'Gà mái đẻ, trọng lượng 1.8–2.2kg. Thịt ngọt mềm hơn gà trống. Thích hợp nấu phở, luộc, hấp muối.',
                'price'    => 160000,
                'unit'     => 'con',
                'stock'    => 18,
                'images'   => ['https://images.unsplash.com/photo-1612170153139-6f881ff067e0?w=1200'],
            ],
            [
                'category' => 'ga',
                'name'     => 'Gà Làm Sẵn (1kg)',
                'desc'     => 'Gà ta làm sẵn, bán theo kg. Đã vặt lông, bỏ nội tạng, rửa sạch. Giao hàng trong ngày.',
                'price'    => 130000,
                'unit'     => 'kg',
                'stock'    => 20.0,
                'images'   => ['https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=1200'],
            ],

            // ── Vịt ───────────────────────────────────────────────────
            [
                'category' => 'vit',
                'name'     => 'Vịt Xiêm (Muscovy) Sống (1 con)',
                'desc'     => 'Vịt Xiêm (vịt Pháp) nuôi thả, trọng lượng 2–3kg/con. Thịt nạc, ít mỡ, đặc trưng nấu vịt nướng, vịt kho gừng.',
                'price'    => 250000,
                'unit'     => 'con',
                'stock'    => 15,
                'images'   => ['https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?w=1200'],
            ],
            [
                'category' => 'vit',
                'name'     => 'Vịt Cỏ Sống (1 con)',
                'desc'     => 'Vịt cỏ nuôi tự nhiên, trọng lượng 1.2–1.8kg/con. Thịt thơm ngon, da giòn. Thích hợp vịt quay, vịt nướng than hoa.',
                'price'    => 150000,
                'unit'     => 'con',
                'stock'    => 20,
                'images'   => ['https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?w=1200'],
            ],

            // ── Chim Cút ─────────────────────────────────────────────
            [
                'category' => 'chim-cut',
                'name'     => 'Chim Cút Sống (1 con)',
                'desc'     => 'Chim cút nuôi chuồng sạch, trọng lượng 150–200g/con. Giàu đạm, ít mỡ. Thích hợp chiên, nướng, kho tiêu.',
                'price'    => 18000,
                'unit'     => 'con',
                'stock'    => 200,
                'images'   => ['https://images.unsplash.com/photo-1444464666168-49d633b86797?w=1200'],
            ],
            [
                'category' => 'chim-cut',
                'name'     => 'Trứng Cút Tươi (1 vỉ 30 quả)',
                'desc'     => 'Trứng cút tươi mới đẻ trong ngày. 30 quả/vỉ. Giàu dinh dưỡng, tốt cho trẻ em và người già.',
                'price'    => 35000,
                'unit'     => 'con',
                'stock'    => 50,
                'images'   => ['https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=1200'],
            ],

            // ── Thức Ăn Chim ─────────────────────────────────────────
            [
                'category' => 'thuc-an-chim',
                'name'     => 'Thóc Nuôi Bồ Câu (5kg)',
                'desc'     => 'Thóc sạch, phơi khô, không mốc. Thức ăn chính cho bồ câu. Túi 5kg, bảo quản nơi khô ráo được 3 tháng.',
                'price'    => 55000,
                'unit'     => 'kg',
                'stock'    => 100.0,
                'images'   => ['https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=1200'],
            ],
            [
                'category' => 'thuc-an-chim',
                'name'     => 'Hỗn Hợp Hạt Dinh Dưỡng (2kg)',
                'desc'     => 'Hỗn hợp đậu xanh, đậu đỏ, ngô, lúa mì. Bổ sung vitamin và khoáng chất cho chim. Túi 2kg.',
                'price'    => 48000,
                'unit'     => 'kg',
                'stock'    => 50.0,
                'images'   => ['https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=1200'],
            ],

            // ── Phụ Kiện ─────────────────────────────────────────────
            [
                'category' => 'phu-kien-nuoi-chim',
                'name'     => 'Máng Ăn Bồ Câu (Nhựa)',
                'desc'     => 'Máng ăn nhựa dành cho bồ câu, dung tích 500ml. Dễ vệ sinh, bền, không gỉ sét. Phù hợp chuồng nuôi gia đình và trang trại nhỏ.',
                'price'    => 25000,
                'unit'     => 'con',
                'stock'    => 75,
                'images'   => ['https://images.unsplash.com/photo-1585559606984-5b685b4b6e9d?w=1200'],
            ],
            [
                'category' => 'phu-kien-nuoi-chim',
                'name'     => 'Lồng Vận Chuyển Chim (Sắt)',
                'desc'     => 'Lồng sắt mạ kẽm để vận chuyển bồ câu và gia cầm. Kích thước 60x40x30cm. Chứa được 10–15 con bồ câu. Tháo lắp dễ dàng.',
                'price'    => 320000,
                'unit'     => 'con',
                'stock'    => 10,
                'images'   => ['https://images.unsplash.com/photo-1585559606984-5b685b4b6e9d?w=1200'],
            ],
        ];

        $manager = new ImageManager(new Driver());

        foreach ($products as $idx => $data) {
            $catId = $categories[$data['category']] ?? null;
            if ($catId === null) {
                $this->command->warn("   Category '{$data['category']}' not found, skipping.");
                continue;
            }

            /** @var ProductModel $product */
            $product = ProductModel::updateOrCreate(
                ['name' => $data['name']],
                [
                    'category_id'    => $catId,
                    'description'    => $data['desc'],
                    'price_vnd'      => $data['price'],
                    'unit_type'      => $data['unit'],
                    'stock_quantity' => (string) $data['stock'],
                    'is_active'      => true,
                ]
            );

            // Remove existing images if re-seeding
            ProductImageModel::where('product_id', $product->id)->delete();
            Storage::disk('public')->deleteDirectory("products/{$product->id}");

            $sortOrder  = 0;
            $isPrimary  = true;

            foreach ($data['images'] as $imageUrl) {
                $this->command->line("   Downloading image for [{$product->name}]...");
                $imageData = $this->fetchImage($imageUrl);

                if ($imageData === null) {
                    $this->command->warn("   ⚠ Failed to download: {$imageUrl}");
                    // Fallback: generate a colored placeholder
                    $imageData = $this->generatePlaceholder($product->name, $idx);
                }

                $filename     = Str::uuid() . '.jpg';
                $originalPath = "products/{$product->id}/{$filename}";
                $thumbPath    = "products/{$product->id}/thumb_{$filename}";

                $img = $manager->read($imageData);

                $resized = $img->scale(width: 1200);
                Storage::disk('public')->put($originalPath, $resized->toJpeg(quality: 85)->toString());

                $thumb = $img->scale(width: 400);
                Storage::disk('public')->put($thumbPath, $thumb->toJpeg(quality: 75)->toString());

                ProductImageModel::create([
                    'product_id'     => $product->id,
                    'path'           => $originalPath,
                    'thumbnail_path' => $thumbPath,
                    'is_primary'     => $isPrimary,
                    'sort_order'     => $sortOrder,
                ]);

                $isPrimary = false;
                $sortOrder++;
            }

            $this->command->info("   ✓ [{$product->name}] — {$sortOrder} image(s)");
        }
    }

    private function fetchImage(string $url): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable) {
            // fall through to null
        }
        return null;
    }

    /** Generate a simple colored placeholder image with text */
    private function generatePlaceholder(string $label, int $seed): string
    {
        $colors = [
            [139, 90,  43],
            [180, 140, 60],
            [100, 140, 70],
            [60,  110, 160],
            [160, 70,  70],
        ];
        [$r, $g, $b] = $colors[$seed % count($colors)];

        $manager = new ImageManager(new Driver());
        $img = $manager->create(1200, 900)->fill("rgb({$r},{$g},{$b})");

        return $img->toJpeg(quality: 80)->toString();
    }
}
