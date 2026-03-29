<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\CartModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartModel>
 */
final class CartModelFactory extends Factory
{
    protected $model = CartModel::class;

    public function definition(): array
    {
        return [
            'token'      => fake()->uuid(),
            'expires_at' => now()->addDays(7),
        ];
    }
}
