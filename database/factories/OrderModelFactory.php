<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\OrderModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderModel>
 */
final class OrderModelFactory extends Factory
{
    protected $model = OrderModel::class;

    public function definition(): array
    {
        return [
            'customer_name'   => fake()->name(),
            'customer_phone'  => '0' . fake()->numerify('#########'),
            'delivery_address' => fake()->address(),
            'order_status'    => 'cho_xac_nhan',
            'payment_method'  => fake()->randomElement(['cod', 'chuyen_khoan']),
            'payment_status'  => 'chua_thanh_toan',
            'delivery_method' => null,
            'total_amount'    => fake()->numberBetween(50000, 5000000),
            'created_by'      => null,
        ];
    }
}
