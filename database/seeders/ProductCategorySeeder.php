<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Illuminate\Database\Seeder;

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
            ['name' => 'Lợn',                   'slug' => 'lon',                   'parent' => null, 'sort' => 3],

            // Chim Bồ Câu children
            ['name' => 'Bồ Câu Sống',           'slug' => 'bo-cau-song',           'parent' => 'chim-bo-cau',     'sort' => 1],
            ['name' => 'Bồ Câu Thịt (Làm Sẵn)', 'slug' => 'bo-cau-thit',           'parent' => 'chim-bo-cau',     'sort' => 2],
            ['name' => 'Bồ Câu Con (Squab)',     'slug' => 'bo-cau-con',            'parent' => 'chim-bo-cau',     'sort' => 3],

            // Gia Cầm children
            ['name' => 'Gà',                     'slug' => 'ga',                    'parent' => 'gia-cam',         'sort' => 1],
            ['name' => 'Vịt',                    'slug' => 'vit',                   'parent' => 'gia-cam',         'sort' => 2],
            ['name' => 'Ngan',                   'slug' => 'ngan',                  'parent' => 'gia-cam',         'sort' => 3],
            ['name' => 'Ngỗng',                  'slug' => 'ngong',                 'parent' => 'gia-cam',         'sort' => 4],
            // Lợn children
            ['name' => 'Lợn Mán',                'slug' => 'lon-man',               'parent' => 'lon',             'sort' => 1],
            ['name' => 'Lợn Rừng',               'slug' => 'lon-rung',              'parent' => 'lon',             'sort' => 2],

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
                'name'     => 'Bồ Câu Pháp Sống',
                'desc'     => 'Bồ câu Pháp (Mimas) thuần chủng nhập giống từ Pháp, được nuôi theo quy trình sạch: chuồng thông thoáng, thức ăn là thóc + hỗn hợp hạt dinh dưỡng, không sử dụng hormone hay kháng sinh tăng trưởng. Trọng lượng đạt 500–600g/con khi xuất bán (khoảng 28–30 ngày tuổi). Thịt mềm, ngọt, màu hồng nhạt, rất thơm. Thích hợp làm thực phẩm cao cấp (tiềm, hầm thuốc bắc, quay) hoặc nuôi sinh sản. Giao hàng còn sống, đóng lồng thoáng khí. Cam kết đúng trọng lượng, bồi hoàn nếu chết trong quá trình vận chuyển.',
                'price'    => 85000,
                'unit'     => 'con',
                'stock'    => 120,
            ],
            [
                'category' => 'bo-cau-song',
                'name'     => 'Bồ Câu Ta Sống',
                'desc'     => 'Bồ câu ta giống địa phương, nuôi thả tự nhiên trong chuồng rộng, ăn thóc và ngô xay. Không tiêm phòng hormone, không dùng cám tăng trọng. Trọng lượng 350–450g/con. Thịt chắc, đậm vị hơn bồ câu Pháp, màu đỏ hồng đặc trưng. Rất thích hợp để nấu cháo bồ câu hầm hạt sen, tiềm thuốc bắc (đương quy, kỷ tử, táo đỏ), hoặc luộc chấm muối chanh. Người già và trẻ nhỏ dùng rất tốt. Giao hàng còn sống, buộc chắc chắn.',
                'price'    => 65000,
                'unit'     => 'con',
                'stock'    => 80,
            ],
            [
                'category' => 'bo-cau-song',
                'name'     => 'Bồ Câu Pháp Sống Cặp Đôi',
                'desc'     => 'Combo 2 con bồ câu Pháp (1 trống + 1 mái) cùng lứa, trọng lượng mỗi con 500–600g. Giá ưu đãi hơn mua lẻ. Phù hợp cho gia đình muốn mua đủ một bữa hoặc khách đặt số lượng lớn theo cặp. Bồ câu Pháp nuôi sạch, không hormone, xuất chuồng đúng ngày. Đóng lồng đôi thoáng khí, vận chuyển an toàn. Cam kết đúng số lượng và trọng lượng, bồi hoàn nếu có sự cố trong vận chuyển.',
                'price'    => 160000,
                'unit'     => 'con',
                'stock'    => 40,
            ],

            // ── Bồ Câu Thịt ──────────────────────────────────────────
            [
                'category' => 'bo-cau-thit',
                'name'     => 'Bồ Câu Pháp Làm Sẵn',
                'desc'     => 'Bồ câu Pháp đã làm sạch hoàn toàn: vặt lông, bỏ đầu, bỏ chân, lấy nội tạng sạch (tim, mề để riêng kèm theo nếu khách muốn). Rửa nước muối loãng, để ráo, đóng túi zip hút chân không. Trọng lượng tịnh 400–480g/con. Bảo quản ngăn mát dùng trong 2 ngày, ngăn đá được 2–3 tháng. Rã đông tự nhiên trong ngăn mát 4–6 tiếng trước khi chế biến để giữ độ ngọt thịt. Thích hợp quay giòn, tiềm, nướng mật ong.',
                'price'    => 95000,
                'unit'     => 'con',
                'stock'    => 60,
            ],
            [
                'category' => 'bo-cau-thit',
                'name'     => 'Bồ Câu Thịt Nguyên Con',
                'desc'     => 'Bồ câu thịt tươi bán theo kg, phù hợp cho nhà hàng, quán ăn đặt số lượng lớn. Đã làm sạch lông, bỏ đầu và chân, lấy nội tạng, rửa sạch. Bán nguyên con không cắt khúc. Trọng lượng tính theo kg tịnh sau khi làm sạch. Thịt tươi giết mổ trong ngày, không qua đông lạnh, màu hồng tươi, không mùi lạ. Giao hàng sáng sớm trước 8h cho các đơn nhà hàng. Đặt trước ít nhất 1 ngày với số lượng từ 5kg trở lên.',
                'price'    => 220000,
                'unit'     => 'kg',
                'stock'    => 15.5,
            ],

            // ── Bồ Câu Con (Squab) ────────────────────────────────────
            [
                'category' => 'bo-cau-con',
                'name'     => 'Squab Bồ Câu Non 28 Ngày',
                'desc'     => 'Squab là bồ câu non chưa tập bay, đúng 25–28 ngày tuổi — thời điểm thịt đạt chất lượng ngon nhất: mềm, không dai, hương thơm nhẹ đặc trưng. Trọng lượng 280–350g/con sau khi làm sạch. Da mỏng, ít mỡ, thịt màu đỏ nhạt rất đẹp. Đây là nguyên liệu cao cấp trong ẩm thực nhà hàng: bồ câu quay Bắc Kinh, bồ câu tiềm bát bửu, bồ câu nướng sốt cam. Đặt trước 2 ngày để đảm bảo đúng độ tuổi. Giao tươi hoặc đã làm sạch theo yêu cầu.',
                'price'    => 120000,
                'unit'     => 'con',
                'stock'    => 30,
            ],

            // ── Gà ────────────────────────────────────────────────────
            [
                'category' => 'ga',
                'name'     => 'Gà Ta Thả Vườn Sống',
                'desc'     => 'Gà ta nuôi thả vườn theo phương pháp truyền thống: ăn thóc, ngô, rau xanh và côn trùng tự nhiên. Không dùng cám tăng trọng, không kháng sinh, không hormone. Thời gian nuôi 4–5 tháng mới xuất chuồng nên thịt săn, da vàng ươm, mỡ vàng đẹp. Trọng lượng hơi 1.5–2kg/con. Thích hợp gà luộc chấm muối chanh, gà hầm sả, gà nướng mọi, hoặc nấu phở. Giao hàng còn sống, đảm bảo con khỏe mạnh.',
                'price'    => 180000,
                'unit'     => 'con',
                'stock'    => 25,
            ],
            [
                'category' => 'ga',
                'name'     => 'Gà Mái Đẻ Sống',
                'desc'     => 'Gà mái đẻ loại thải sau chu kỳ đẻ trứng 12–18 tháng. Trọng lượng 1.8–2.2kg/con. Thịt mái ngọt hơn thịt trống, ít xơ hơn gà già, phù hợp nấu lâu (hầm, tần). Da có màu vàng nhạt, lớp mỡ vừa phải. Đặc biệt thích hợp nấu phở gà, gà hầm hạt sen, gà tiềm đông trùng hạ thảo. Gà còn sống, khỏe mạnh, không bệnh tật. Giao hàng buổi sáng, cam kết đúng trọng lượng.',
                'price'    => 160000,
                'unit'     => 'con',
                'stock'    => 18,
            ],
            [
                'category' => 'ga',
                'name'     => 'Gà Ta Làm Sẵn',
                'desc'     => 'Gà ta thả vườn đã làm sạch hoàn toàn: vặt lông, bỏ nội tạng, rửa nước muối loãng, để ráo nước. Bán theo kg tịnh sau khi làm sạch. Thịt tươi giết mổ trong buổi sáng, không qua đông lạnh. Da vàng ươm đặc trưng gà ta thả vườn. Thích hợp cho gia đình muốn mua sẵn về chế biến ngay, không mất công làm lông. Giao hàng trước 10h sáng. Đặt trước từ tối hôm trước để đảm bảo hàng.',
                'price'    => 130000,
                'unit'     => 'kg',
                'stock'    => 20.0,
            ],

            // ── Vịt ───────────────────────────────────────────────────
            [
                'category' => 'vit',
                'name'     => 'Vịt Cỏ Sống',
                'desc'     => 'Vịt cỏ giống bản địa miền Bắc, còn gọi là vịt ta hay vịt cánh sẻ. Nuôi thả đồng, ăn lúa rơi, cá tép tự nhiên và cám gạo, không dùng thức ăn công nghiệp. Thời gian nuôi 3–4 tháng. Trọng lượng hơi 1.5–2.5kg/con. Thịt chắc, ít mỡ, thơm ngon đặc trưng. Da mỏng, giòn sau khi chế biến. Đặc biệt ngon khi làm vịt nướng chao, vịt om sấu, vịt nấu măng. Giao hàng còn sống, buộc chân an toàn.',
                'price'    => 95000,
                'unit'     => 'con',
                'stock'    => 30,
            ],
            [
                'category' => 'vit',
                'name'     => 'Vịt Trời Sống',
                'desc'     => 'Vịt trời (tên khoa học Anas platyrhynchos) nuôi bán hoang dã trong ao rộng thoáng, ăn cá tép tự nhiên và lúa. Không phải vịt nhà, con nhỏ hơn nhưng thịt ngon hơn hẳn. Trọng lượng 0.8–1.5kg/con. Thịt màu đỏ đậm, chắc, thơm mùi đặc trưng của vịt trời — hoàn toàn khác với vịt nhà thông thường. Rất được ưa chuộng ở các nhà hàng đặc sản miền núi phía Bắc (Lạng Sơn, Cao Bằng, Hà Giang). Thích hợp: om với rau tía tô + gừng, quay mắc khén, nướng than hoa. Số lượng hạn chế, đặt trước 2–3 ngày.',
                'price'    => 250000,
                'unit'     => 'con',
                'stock'    => 15,
            ],
            [
                'category' => 'vit',
                'name'     => 'Vịt Siêu Thịt Sống',
                'desc'     => 'Vịt siêu thịt giống Cherry Valley nhập từ Anh, được lai tạo chuyên cho thịt: tăng trọng nhanh (45–50 ngày đạt 3kg), tỷ lệ thịt/xương cao, ức dày, ít mỡ. Nuôi chuồng có hệ thống thông gió, ăn cám viên công nghiệp cân đối dinh dưỡng. Trọng lượng hơi 3–4kg/con khi xuất bán. Thịt màu trắng hồng, mềm, phù hợp tiêu thụ đại trà: vịt luộc, vịt quay, vịt om sấu, bún vịt. Giá hợp lý, phổ biến ở chợ Hà Nội và các tỉnh đồng bằng Bắc Bộ.',
                'price'    => 190000,
                'unit'     => 'con',
                'stock'    => 25,
            ],

            // ── Ngan ──────────────────────────────────────────────────
            [
                'category' => 'ngan',
                'name'     => 'Ngan Ta Sống',
                'desc'     => 'Ngan ta (vịt xiêm nội) giống địa phương, có nguồn gốc từ Nam Mỹ nhưng đã thích nghi với điều kiện Việt Nam qua nhiều thế hệ. Lông đen tuyền hoặc đen trắng xen, đầu có mào đỏ đặc trưng. Nuôi thả tự nhiên, ăn cám gạo, rau muống, cá tạp. Thời gian nuôi 3–4 tháng. Trọng lượng con đực 3–3.5kg, con mái 2–2.2kg. Thịt đỏ đậm, chắc, thơm và ít mỡ hơn vịt — rất được ưa chuộng ở miền Bắc. Thích hợp: ngan om sấu, ngan nấu giả cầy, ngan hấp bia gừng. Giao hàng còn sống.',
                'price'    => 200000,
                'unit'     => 'con',
                'stock'    => 20,
            ],
            [
                'category' => 'ngan',
                'name'     => 'Ngan Pháp R71 Sống',
                'desc'     => 'Ngan Pháp dòng R71 (Muscovy trắng cao sản), nhập giống từ Pháp qua Công ty Giống gia cầm Đại Xuyên. Đây là dòng ngan to nhất và nhiều thịt nhất hiện nay tại Việt Nam. Con đực trưởng thành đạt 5.5–6.5kg, con mái 2.8–3.2kg. Lông trắng tuyền, thịt trắng hồng, tỷ lệ nạc cao, ít mỡ, da giòn sau khi chế biến. Nuôi chuồng sạch, thức ăn cân đối, xuất bán sau 80–90 ngày. Rất thích hợp cho nhà hàng đặc sản cần nguyên liệu to, đẹp: ngan quay, ngan hấp, ngan om sấu kiểu nhà hàng. Đặt trước 3–5 ngày với số lượng lớn.',
                'price'    => 380000,
                'unit'     => 'con',
                'stock'    => 12,
            ],

            // ── Ngỗng ─────────────────────────────────────────────────
            [
                'category' => 'ngong',
                'name'     => 'Ngỗng Cỏ Sống',
                'desc'     => 'Ngỗng ta giống bản địa, phổ biến ở vùng đồng bằng và trung du Bắc Bộ (Hà Nam, Nam Định, Thái Bình). Nuôi thả đồng, ăn cỏ, rau muống, lúa rơi — hoàn toàn tự nhiên. Thời gian nuôi 5–6 tháng. Trọng lượng hơi 4–6kg/con. Thịt chắc, ngọt, thơm đặc trưng, lớp mỡ dưới da vừa phải tạo độ béo ngậy. Da vàng nhạt sau khi luộc, rất đẹp. Ngỗng ta là đặc sản bữa cỗ truyền thống miền Bắc: ngỗng luộc chấm mắm gừng, ngỗng om sấu, tiết canh ngỗng. Số lượng ít, nên đặt trước 3–5 ngày.',
                'price'    => 350000,
                'unit'     => 'con',
                'stock'    => 15,
            ],
            [
                'category' => 'ngong',
                'name'     => 'Ngỗng Sư Tử Sống',
                'desc'     => 'Ngỗng Sư Tử (còn gọi là ngỗng Trung Quốc hay ngỗng Phi) đặc sản vùng ngoại thành Hà Nội và đồng bằng sông Hồng. Nhận biết qua cái mào thịt to màu đen trên đầu rất đặc trưng, lông xám đen hoặc nâu xám. Đây là giống ngỗng to nhất Việt Nam: con đực trưởng thành 6–8kg, con mái 4.5–5.5kg. Thịt đỏ đậm, chắc, nhiều nạc, hương vị đậm đà hơn ngỗng ta thông thường. Nuôi thả ao rộng, ăn rau cỏ và cám gạo tự nhiên. Phù hợp làm ngỗng quay nguyên con cho tiệc, ngỗng nhồi táo nướng, hoặc làm cỗ đám. Đặt trước 5–7 ngày.',
                'price'    => 650000,
                'unit'     => 'con',
                'stock'    => 10,
            ],
            [
                'category' => 'ngong',
                'name'     => 'Ngỗng Lai Sống',
                'desc'     => 'Ngỗng lai giữa ngỗng Sư Tử (bố) × ngỗng ta (mẹ), phổ biến ở Hà Tây cũ (Hà Nội mở rộng) và các tỉnh lân cận. Kết hợp ưu điểm của cả hai dòng: to hơn ngỗng ta thuần (4–5.5kg), thịt mềm hơn ngỗng Sư Tử thuần, tăng trọng nhanh hơn, chi phí nuôi thấp hơn. Lông thường xám trắng hoặc trắng xen xám. Thịt ngon, da vừa dày vừa giòn khi chế biến. Mức giá trung bình, phù hợp gia đình và tiệc quy mô vừa. Thích hợp: ngỗng luộc, ngỗng nướng lá chuối, om rau củ. Giao hàng còn sống.',
                'price'    => 480000,
                'unit'     => 'con',
                'stock'    => 18,
            ],

            // ── Lợn Mán ───────────────────────────────────────────────
            [
                'category' => 'lon-man',
                'name'     => 'Lợn Mán Hơi Nguyên Con',
                'desc'     => 'Lợn mán (lợn mọi, heo mọi) là giống lợn bản địa của đồng bào dân tộc vùng Tây Bắc (Sơn La, Điện Biên, Lai Châu). Nuôi hoàn toàn thả rông trên đồi núi, tự kiếm ăn: rễ cây, quả rừng, rau cỏ tự nhiên, bổ sung thêm ngô và sắn của bà con. Không dùng cám công nghiệp, không kháng sinh, không hormone. Thời gian nuôi 8–12 tháng mới đạt trọng lượng xuất bán. Trọng lượng hơi 15–25kg/con — nhỏ hơn lợn nhà nhưng thịt cực kỳ chắc và thơm. Lớp mỡ mỏng (1–2cm), thịt nạc màu đỏ đậm, vân mỡ đẹp như thịt bò wagyu. Hương vị ngọt, đậm đà, hoàn toàn khác thịt lợn siêu nạc công nghiệp. Thích hợp: lợn mán quay giòn da, nướng nguyên con trên than củi, hay chế biến thành đặc sản tiệc Tết. Bán theo kg hơi (cân sống), mổ ngay tại chỗ nếu khách yêu cầu.',
                'price'    => 135000,
                'unit'     => 'kg',
                'stock'    => 80.0,
            ],

            // ── Lợn Rừng ──────────────────────────────────────────────
            [
                'category' => 'lon-rung',
                'name'     => 'Lợn Rừng Thuần Chủng F1 Hơi',
                'desc'     => 'Lợn rừng F1 là con lai thế hệ thứ nhất giữa lợn rừng đực thuần chủng (Sus scrofa) × lợn mọi cái bản địa. Nuôi bán hoang dã trong khu vực rừng có rào lưới rộng, tự kiếm ăn rễ cây, côn trùng, quả rừng — bổ sung thêm cám gạo và rau củ. Không dùng thức ăn công nghiệp, không kháng sinh. Thời gian nuôi 8–10 tháng đạt 25–35kg. Đây là dòng giữ nguyên bản chất lợn rừng nhiều nhất: thịt đỏ đậm như thịt bò, vân mỡ trắng xen đẹp, lớp mỡ rất mỏng (dưới 1cm). Hương thơm đặc biệt, ngọt sâu, không hôi như lợn nhà. Xương chắc, nước hầm ngọt. Thích hợp: lẩu lợn rừng lá rừng, nướng muối ớt, quay mắc khén kiểu Tây Bắc. Bán kg hơi, thu mua trực tiếp từ trang trại.',
                'price'    => 250000,
                'unit'     => 'kg',
                'stock'    => 50.0,
            ],
            [
                'category' => 'lon-rung',
                'name'     => 'Lợn Rừng Lai Tuyển Chọn Hơi',
                'desc'     => 'Lợn rừng lai tuyển chọn là dòng lợn rừng F2–F3, được lai tiếp với lợn mọi hoặc lợn địa phương để cải thiện tốc độ tăng trọng mà vẫn giữ phần lớn đặc tính lợn rừng. Nuôi trong chuồng bán thả rộng, ăn cám gạo + rau củ + thức ăn thô tự nhiên, không dùng cám tăng trọng công nghiệp. Thời gian nuôi 6–8 tháng đạt 35–55kg. Thịt vẫn đỏ hơn lợn nhà thông thường, ít mỡ, thơm ngon, nhưng giá thành thấp hơn F1 thuần. Phù hợp với khách muốn thưởng thức thịt lợn rừng ngon mà ngân sách hợp lý hơn. Thích hợp nấu các món đặc sản: lẩu, nướng, rang muối. Bán kg hơi.',
                'price'    => 190000,
                'unit'     => 'kg',
                'stock'    => 70.0,
            ],

        ];

        foreach ($products as $data) {
            $catId = $categories[$data['category']] ?? null;
            if ($catId === null) {
                $this->command->warn("   Category '{$data['category']}' not found, skipping.");
                continue;
            }

            ProductModel::updateOrCreate(
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

            $this->command->info("   ✓ {$data['name']}");
        }
    }
}
