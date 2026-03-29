<?php

use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('Admin Dashboard', function () {
    function makeAdminForDashboard(): string
    {
        $admin = UserModel::factory()->create();

        return $admin->createToken('test')->plainTextToken;
    }

    it('returns 200 with orders_by_status containing all 5 status keys', function () {
        $token = makeAdminForDashboard();

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'orders_by_status' => [
                        'cho_xac_nhan',
                        'xac_nhan',
                        'dang_giao',
                        'hoan_thanh',
                        'huy',
                    ],
                ],
            ]);

        $ordersByStatus = $response->json('data.orders_by_status');
        foreach (['cho_xac_nhan', 'xac_nhan', 'dang_giao', 'hoan_thanh', 'huy'] as $status) {
            expect($ordersByStatus)->toHaveKey($status);
            expect($ordersByStatus[$status])->toBeInt();
        }
    });

    it('returns correct counts when orders exist in various statuses', function () {
        $token = makeAdminForDashboard();

        // Create 3 cho_xac_nhan and 1 xac_nhan orders
        OrderModel::factory()->count(3)->create(['order_status' => 'cho_xac_nhan']);
        OrderModel::factory()->count(1)->create(['order_status' => 'xac_nhan']);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.orders_by_status.cho_xac_nhan', 3)
            ->assertJsonPath('data.orders_by_status.xac_nhan', 1)
            ->assertJsonPath('data.orders_by_status.dang_giao', 0)
            ->assertJsonPath('data.orders_by_status.hoan_thanh', 0)
            ->assertJsonPath('data.orders_by_status.huy', 0);
    });

    it('returns 0 for all statuses when database is empty', function () {
        $token = makeAdminForDashboard();

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.orders_by_status.cho_xac_nhan', 0)
            ->assertJsonPath('data.orders_by_status.xac_nhan', 0)
            ->assertJsonPath('data.orders_by_status.dang_giao', 0)
            ->assertJsonPath('data.orders_by_status.hoan_thanh', 0)
            ->assertJsonPath('data.orders_by_status.huy', 0);
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(401);
    });
});
