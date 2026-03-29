<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Carbon\Carbon;
use Database\Factories\CartModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon $expires_at
 */
final class CartModel extends Model
{
    /** @use HasFactory<CartModelFactory> */
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CartModelFactory
    {
        return CartModelFactory::new();
    }

    /** @return HasMany<CartItemModel, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItemModel::class, 'cart_id');
    }
}
