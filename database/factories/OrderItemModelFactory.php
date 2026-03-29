<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\OrderItemModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemModel>
 */
final class OrderItemModelFactory extends Factory
{
    protected $model = OrderItemModel::class;

    public function definition(): array
    {
        $priceVnd = fake()->numberBetween(10000, 500000);
        $quantity  = fake()->randomFloat(3, 0.5, 10);
        $subtotalVnd = (int) round($priceVnd * $quantity);

        return [
            'order_id'     => OrderModelFactory::new(),
            'product_id'   => ProductModelFactory::new(),
            'product_name' => fake()->words(3, true),
            'price_vnd'    => $priceVnd,
            'quantity'     => $quantity,
            'subtotal_vnd' => $subtotalVnd,
        ];
    }
}
