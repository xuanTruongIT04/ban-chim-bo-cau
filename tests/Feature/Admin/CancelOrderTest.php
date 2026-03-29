<?php

use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Database\Factories\OrderItemModelFactory;

describe('Cancel Order', function () {
    function makeAdminForCancel(): string
    {
        $admin = UserModel::factory()->create();

        return $admin->createToken('test')->plainTextToken;
    }

    it('cancels order and restores stock in same transaction', function () {
        $token = makeAdminForCancel();

        // Create products with known stock (already decremented after order creation)
        $p1 = ProductModel::factory()->create(['stock_quantity' => '7.000', 'price_vnd' => 50000]);
        $p2 = ProductModel::factory()->create(['stock_quantity' => '8.000', 'price_vnd' => 30000]);

        // Create order with items that represent 3 units from p1 and 2 units from p2
        $order = OrderModel::factory()->create([
            'order_status' => 'cho_xac_nhan',
            'total_amount' => '210000',
        ]);

        OrderItemModelFactory::new()->create([
            'order_id'     => $order->id,
            'product_id'   => $p1->id,
            'product_name' => $p1->name,
            'price_vnd'    => 50000,
            'quantity'     => '3.000',
            'subtotal_vnd' => 150000,
        ]);

        OrderItemModelFactory::new()->create([
            'order_id'     => $order->id,
            'product_id'   => $p2->id,
            'product_name' => $p2->name,
            'price_vnd'    => 30000,
            'quantity'     => '2.000',
            'subtotal_vnd' => 60000,
        ]);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_status', 'huy');

        // Stock should be restored
        $this->assertDatabaseHas('products', [
            'id'             => $p1->id,
            'stock_quantity' => '10.000',
        ]);

        $this->assertDatabaseHas('products', [
            'id'             => $p2->id,
            'stock_quantity' => '10.000',
        ]);
    });

    it('cancels order from xac_nhan state', function () {
        $token = makeAdminForCancel();
        $order = OrderModel::factory()->create(['order_status' => 'xac_nhan']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'huy');
    });

    it('cancels order from dang_giao state', function () {
        $token = makeAdminForCancel();
        $order = OrderModel::factory()->create(['order_status' => 'dang_giao']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'huy');
    });

    it('rejects cancellation of hoan_thanh order', function () {
        $token = makeAdminForCancel();
        $order = OrderModel::factory()->create(['order_status' => 'hoan_thanh']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_ORDER_TRANSITION');
    });

    it('rejects cancellation of already-cancelled order', function () {
        $token = makeAdminForCancel();
        $order = OrderModel::factory()->create(['order_status' => 'huy']);

        $response = $this->withToken($token)
            ->postJson("/api/v1/admin/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_ORDER_TRANSITION');
    });
});
