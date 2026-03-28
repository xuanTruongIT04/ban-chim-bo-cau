<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryModel>
 */
final class CategoryModelFactory extends Factory
{
    protected $model = CategoryModel::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->words(2, true),
            'slug'        => fake()->unique()->slug(2),
            'parent_id'   => null,
            'description' => fake()->sentence(),
            'sort_order'  => 0,
            'is_active'   => true,
        ];
    }
}
