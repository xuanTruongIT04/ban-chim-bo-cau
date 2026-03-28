<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductModel>
 */
final class ProductModelFactory extends Factory
{
    protected $model = ProductModel::class;

    public function definition(): array
    {
        return [
            'category_id'    => CategoryModel::factory(),
            'name'           => fake()->words(3, true),
            'description'    => fake()->paragraph(),
            'price_vnd'      => fake()->numberBetween(10000, 500000),
            'unit_type'      => fake()->randomElement(['con', 'kg']),
            'stock_quantity' => fake()->randomFloat(3, 0, 100),
            'is_active'      => true,
        ];
    }
}
