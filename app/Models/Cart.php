<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode as MathRoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['session_id', 'customer_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isEmpty(): Attribute
    {
        return Attribute::get(fn (): bool => $this->cartItems->isEmpty());
    }

    public function total(): Attribute
    {
        return Attribute::get(function () {
            $amount = $this->cartItems->reduce(function (BigDecimal $carry, CartItem $item) {
                return $carry->plus(
                    $item->product->price->getAmount()->multipliedBy($item->quantity)
                );
            }, BigDecimal::zero());

            $currency = $this->cartItems->first()?->product?->price?->getCurrency()->getCurrencyCode() ?? 'SAR';

            return Money::of($amount, $currency, roundingMode: MathRoundingMode::HALF_UP);
        });
    }

    public function hasProduct(Product $product): bool
    {
        return $this->cartItems()->where('product_id', $product->id)->exists();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** Returns cart item matching product if any */
    public function cartItem(Product $product): ?CartItem
    {
        if ($this->relationLoaded('cartItems')) {
            return $this->cartItems->firstWhere('product_id', $product->id);
        }

        return $this->cartItems()->where('product_id', $product->id)->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'customer_id' => 'integer',
        ];
    }
}
