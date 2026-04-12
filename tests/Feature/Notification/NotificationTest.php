<?php

use App\Infrastructure\Notifications\NewOrderNotification;
use App\Infrastructure\Persistence\Eloquent\Models\CartItemModel;
use App\Infrastructure\Persistence\Eloquent\Models\CartModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

describe('NewOrderNotification', function () {

    it('queues email notification after customer order is placed', function () {
        Notification::fake();

        $admin = UserModel::factory()->create();

        $cart    = CartModel::factory()->create();
        $product = ProductModel::factory()->create([
            'stock_quantity' => '10.000',
            'price_vnd'      => 50000,
            'is_active'      => true,
        ]);
        CartItemModel::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => '2',
        ]);

        $this->withHeaders([
            'X-Cart-Token'    => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', [
            'customer_name'    => 'Nguyen Van A',
            'customer_phone'   => '0901234567',
            'delivery_address' => '123 Duong ABC, TPHCM',
        ])->assertStatus(201);

        Notification::assertSentTo($admin, NewOrderNotification::class);
    });

    it('queues email notification after admin manual order', function () {
        Notification::fake();

        $admin = UserModel::factory()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $product = ProductModel::factory()->create([
            'stock_quantity' => '20.000',
            'price_vnd'      => 50000,
            'is_active'      => true,
        ]);

        $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/v1/admin/orders', [
                'customer_name'    => 'Tran Thi B',
                'customer_phone'   => '0909876543',
                'delivery_address' => '456 Duong XYZ, Ha Noi',
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => '3'],
                ],
            ])->assertStatus(201);

        Notification::assertSentTo($admin, NewOrderNotification::class);
    });

    it('notification email contains product names, quantities, and address', function () {
        Notification::fake();

        $admin = UserModel::factory()->create();

        $productName = 'Chim bo cau song';
        $cart        = CartModel::factory()->create();
        $product     = ProductModel::factory()->create([
            'name'           => $productName,
            'stock_quantity' => '10.000',
            'price_vnd'      => 75000,
            'is_active'      => true,
        ]);
        CartItemModel::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => '3',
        ]);

        $deliveryAddress = '789 Duong DEF, Da Nang';

        $this->withHeaders([
            'X-Cart-Token'    => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', [
            'customer_name'    => 'Le Van C',
            'customer_phone'   => '0912345678',
            'delivery_address' => $deliveryAddress,
        ])->assertStatus(201);

        Notification::assertSentTo(
            $admin,
            NewOrderNotification::class,
            function (NewOrderNotification $notification) use ($admin, $productName, $deliveryAddress) {
                $mail     = $notification->toMail($admin);
                $rendered = (string) $mail->render();

                \PHPUnit\Framework\assertStringContainsString($productName, $rendered);
                \PHPUnit\Framework\assertStringContainsString('Le Van C', $rendered);
                \PHPUnit\Framework\assertStringContainsString($deliveryAddress, $rendered);
                \PHPUnit\Framework\assertStringContainsString('Tong cong', $rendered);
                \PHPUnit\Framework\assertStringContainsString('3', $rendered); // quantity

                return true;
            }
        );
    });

    it('notification is queued with afterCommit flag', function () {
        // Build a minimal Order domain entity to instantiate the notification
        $order = new \App\Domain\Order\Entities\Order(
            id: 1,
            customerName: 'Test',
            customerPhone: '0901234567',
            deliveryAddress: 'Test address',
            orderStatus: \App\Domain\Order\Enums\OrderStatus::ChoXacNhan,
            paymentMethod: \App\Domain\Order\Enums\PaymentMethod::Cod,
            paymentStatus: \App\Domain\Order\Enums\PaymentStatus::ChuaThanhToan,
            deliveryMethod: null,
            totalAmount: '100000',
            createdBy: null,
            items: [],
            createdAt: null,
            updatedAt: null,
        );

        $notification = new NewOrderNotification($order);

        expect($notification->afterCommit)->toBeTrue();
    });

    it('notification email subject includes order id', function () {
        Notification::fake();

        $admin = UserModel::factory()->create();

        $cart    = CartModel::factory()->create();
        $product = ProductModel::factory()->create([
            'stock_quantity' => '10.000',
            'price_vnd'      => 50000,
            'is_active'      => true,
        ]);
        CartItemModel::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => '1',
        ]);

        $response = $this->withHeaders([
            'X-Cart-Token'    => $cart->token,
            'Idempotency-Key' => (string) Str::uuid(),
        ])->postJson('/api/v1/checkout', [
            'customer_name'    => 'Pham Van D',
            'customer_phone'   => '0987654321',
            'delivery_address' => '321 Duong GHI',
        ])->assertStatus(201);

        $orderId = $response->json('data.id');

        Notification::assertSentTo(
            $admin,
            NewOrderNotification::class,
            function (NewOrderNotification $notification) use ($admin, $orderId) {
                $mail = $notification->toMail($admin);

                expect($mail->subject)->toContain((string) $orderId);

                return true;
            }
        );
    });
});
