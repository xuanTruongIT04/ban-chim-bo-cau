<?php

use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('Admin Order List', function () {
    function makeAdminForOrderList(): string
    {
        $admin = UserModel::factory()->create();

        return $admin->createToken('test')->plainTextToken;
    }

    it('returns 200 with paginated response structure', function () {
        $token = makeAdminForOrderList();
        OrderModel::factory()->count(3)->create(['order_status' => 'cho_xac_nhan']);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    });

    it('default sort is created_at descending (newest first)', function () {
        $token = makeAdminForOrderList();

        $older = OrderModel::factory()->create([
            'order_status' => 'cho_xac_nhan',
            'created_at'   => now()->subDays(2),
        ]);
        $newer = OrderModel::factory()->create([
            'order_status' => 'cho_xac_nhan',
            'created_at'   => now()->subDays(1),
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data[0]['id'])->toBe($newer->id);
        expect($data[1]['id'])->toBe($older->id);
    });

    it('filters orders by status', function () {
        $token = makeAdminForOrderList();

        OrderModel::factory()->count(2)->create(['order_status' => 'cho_xac_nhan']);
        OrderModel::factory()->count(1)->create(['order_status' => 'xac_nhan']);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders?filter[status]=cho_xac_nhan');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBe(2);
        foreach ($data as $order) {
            expect($order['order_status'])->toBe('cho_xac_nhan');
        }
    });

    it('filters orders by date range', function () {
        $token = makeAdminForOrderList();

        // Order in March 2026
        OrderModel::factory()->create([
            'order_status' => 'cho_xac_nhan',
            'created_at'   => '2026-03-15 10:00:00',
        ]);
        // Order in January 2026 (outside range)
        OrderModel::factory()->create([
            'order_status' => 'cho_xac_nhan',
            'created_at'   => '2026-01-10 10:00:00',
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders?filter[date_from]=2026-03-01&filter[date_to]=2026-03-31');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBe(1);
    });

    it('filters orders by customer name search', function () {
        $token = makeAdminForOrderList();

        OrderModel::factory()->create([
            'customer_name' => 'Nguyen Van A',
            'order_status'  => 'cho_xac_nhan',
        ]);
        OrderModel::factory()->create([
            'customer_name' => 'Tran Thi B',
            'order_status'  => 'cho_xac_nhan',
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders?filter[search]=Nguyen');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBe(1);
        expect($data[0]['customer_name'])->toBe('Nguyen Van A');
    });

    it('filters orders by customer phone search', function () {
        $token = makeAdminForOrderList();

        OrderModel::factory()->create([
            'customer_phone' => '0901234567',
            'order_status'   => 'cho_xac_nhan',
        ]);
        OrderModel::factory()->create([
            'customer_phone' => '0987654321',
            'order_status'   => 'cho_xac_nhan',
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders?filter[search]=0901');

        $response->assertStatus(200);
        $data = $response->json('data');
        expect(count($data))->toBe(1);
        expect($data[0]['customer_phone'])->toBe('0901234567');
    });

    it('paginates with default 20 per page', function () {
        $token = makeAdminForOrderList();

        OrderModel::factory()->count(5)->create(['order_status' => 'cho_xac_nhan']);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 1);
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/v1/admin/orders');

        $response->assertStatus(401);
    });
});
