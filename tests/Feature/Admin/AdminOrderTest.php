<?php

use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;

describe('Admin Orders', function () {
    function makeAdminForOrder(): string
    {
        $admin = UserModel::factory()->create();

        return $admin->createToken('test')->plainTextToken;
    }

    it('admin creates manual order with same atomic lock', function () {
        $token   = makeAdminForOrder();
        $product = ProductModel::factory()->create([
            'stock_quantity' => '20.000',
            'price_vnd'      => 50000,
            'is_active'      => true,
        ]);

        $response = $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/v1/admin/orders', [
                'customer_name'    => 'Trần Thị B',
                'customer_phone'   => '0909876543',
                'delivery_address' => '456 Đường XYZ, Hà Nội',
                'payment_method'   => 'cod',
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => '3'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_status', 'cho_xac_nhan')
            ->assertJsonPath('data.payment_status', 'chua_thanh_toan');

        // created_by should be set to admin ID
        $adminId = UserModel::orderByDesc('id')->first()->id;
        $response->assertJsonPath('data.created_by', $adminId);

        // Stock decremented
        $this->assertDatabaseHas('products', [
            'id'             => $product->id,
            'stock_quantity' => '17.000',
        ]);
    });

    it('admin views order detail', function () {
        $token = makeAdminForOrder();

        $order = OrderModel::factory()->create([
            'customer_name'    => 'Nguyễn Văn C',
            'customer_phone'   => '0912345678',
            'delivery_address' => '789 Đường DEF',
            'order_status'     => 'cho_xac_nhan',
            'payment_method'   => 'cod',
            'payment_status'   => 'chua_thanh_toan',
            'delivery_method'  => null,
            'total_amount'     => '100000',
            'created_by'       => null,
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer_name', 'Nguyễn Văn C')
            ->assertJsonPath('data.customer_phone', '0912345678')
            ->assertJsonPath('data.delivery_address', '789 Đường DEF')
            ->assertJsonPath('data.order_status', 'cho_xac_nhan')
            ->assertJsonPath('data.payment_method', 'cod')
            ->assertJsonPath('data.payment_status', 'chua_thanh_toan')
            ->assertJsonStructure(['data' => ['items']]);
    });

    it('admin manual order rejects insufficient stock', function () {
        $token   = makeAdminForOrder();
        $product = ProductModel::factory()->create([
            'stock_quantity' => '2.000',
            'is_active'      => true,
        ]);

        $response = $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/v1/admin/orders', [
                'customer_name'    => 'Lê Văn D',
                'customer_phone'   => '0987654321',
                'delivery_address' => '321 Đường GHI',
                'payment_method'   => 'cod',
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => '10'],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');
    });

    it('returns 404 for nonexistent order', function () {
        $token = makeAdminForOrder();

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/orders/99999');

        $response->assertStatus(404)
            ->assertJsonPath('code', 'ORDER_NOT_FOUND');
    });

    it('unauthenticated user cannot access admin orders', function () {
        $product = ProductModel::factory()->create(['stock_quantity' => '10.000']);

        $response = $this->postJson('/api/v1/admin/orders', [
            'customer_name'    => 'Test',
            'customer_phone'   => '0901234567',
            'delivery_address' => 'Test address',
            'payment_method'   => 'cod',
            'items'            => [
                ['product_id' => $product->id, 'quantity' => '1'],
            ],
        ]);

        $response->assertStatus(401);
    });
});
