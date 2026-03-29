<?php

use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

describe('Payment', function () {
    function makeAdminForPayment(): string
    {
        $admin = UserModel::factory()->create();

        return $admin->createToken('test')->plainTextToken;
    }

    it('new order has payment_status chua_thanh_toan', function () {
        $token = makeAdminForPayment();
        $order = OrderModel::factory()->create([
            'order_status'   => 'cho_xac_nhan',
            'payment_status' => 'chua_thanh_toan',
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'cho_xac_nhan')
            ->assertJsonPath('data.payment_status', 'chua_thanh_toan');
    });

    it('admin confirms payment', function () {
        $token = makeAdminForPayment();
        $order = OrderModel::factory()->create([
            'order_status'   => 'xac_nhan',
            'payment_status' => 'chua_thanh_toan',
        ]);

        $response = $this->withToken($token)
            ->patchJson("/api/v1/admin/orders/{$order->id}/payment-status");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'da_thanh_toan');
    });

    it('confirming payment is idempotent', function () {
        $token = makeAdminForPayment();
        $order = OrderModel::factory()->create([
            'order_status'   => 'xac_nhan',
            'payment_status' => 'chua_thanh_toan',
        ]);

        // First confirmation
        $this->withToken($token)
            ->patchJson("/api/v1/admin/orders/{$order->id}/payment-status")
            ->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'da_thanh_toan');

        // Second confirmation — should still return 200 with da_thanh_toan
        $response = $this->withToken($token)
            ->patchJson("/api/v1/admin/orders/{$order->id}/payment-status");

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'da_thanh_toan');
    });

    it('payment_status is independent of order_status', function () {
        $token = makeAdminForPayment();
        $order = OrderModel::factory()->create([
            'order_status'   => 'cho_xac_nhan',
            'payment_status' => 'chua_thanh_toan',
        ]);

        // Change order_status to xac_nhan
        $this->withToken($token)
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'xac_nhan'])
            ->assertStatus(200)
            ->assertJsonPath('data.order_status', 'xac_nhan');

        // payment_status should remain chua_thanh_toan
        $response = $this->withToken($token)
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'xac_nhan')
            ->assertJsonPath('data.payment_status', 'chua_thanh_toan');
    });

    it('cannot confirm payment on cancelled order', function () {
        $token = makeAdminForPayment();
        $order = OrderModel::factory()->create([
            'order_status'   => 'huy',
            'payment_status' => 'chua_thanh_toan',
        ]);

        $response = $this->withToken($token)
            ->patchJson("/api/v1/admin/orders/{$order->id}/payment-status");

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_ORDER_TRANSITION');
    });
});
