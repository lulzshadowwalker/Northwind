<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Contracts\Payable;
use App\Enums\OrderStatus;
use App\Support\PayableItem;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model implements Payable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "order_number",
        "status",
        "subtotal",
        "discount_amount",
        "total",
        "promo_code",
        "customer_id",
        "shipping_address",
        "shipping_city",
        "shipping_state",
        "shipping_zip",
        "shipping_country",
        "billing_address",
        "billing_city",
        "billing_state",
        "billing_zip",
        "billing_country",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "id" => "integer",
            "status" => OrderStatus::class,
            "subtotal" => "decimal:2",
            "discount_amount" => "decimal:2",
            "total" => "decimal:2",
            "price" => MoneyCast::class . ":total",
            "customer_id" => "integer",
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, "payable");
    }

    public function items(): array
    {
        return $this->orderItems
            ->map(function (OrderItem $item) {
                // Manually create Money object since cast might not be working
                $price = \Brick\Money\Money::of(
                    $item->unit_price,
                    "SAR",
                    roundingMode: \Brick\Math\RoundingMode::HALF_UP,
                );

                return new PayableItem(
                    $item->product_name,
                    $price,
                    $item->quantity,
                );
            })
            ->toArray();
    }

    public function price(): Money
    {
        // Ensure orderItems are loaded
        if (!$this->relationLoaded("orderItems")) {
            $this->load("orderItems");
        }

        // Calculate subtotal from order items using proper decimal arithmetic
        $subtotal = $this->orderItems->reduce(function ($carry, $item) {
            $itemTotal = \Brick\Math\BigDecimal::of(
                $item->unit_price,
            )->multipliedBy(\Brick\Math\BigDecimal::of($item->quantity));
            return $carry->plus($itemTotal);
        }, \Brick\Math\BigDecimal::zero());

        // Fallback to stored total if calculation fails
        if ($subtotal->isZero() || $subtotal->isNegative()) {
            $subtotal = \Brick\Math\BigDecimal::of($this->total ?? 0);
        }

        // Add 15% VAT using proper decimal arithmetic
        $vatRate = \Brick\Math\BigDecimal::of("0.15");
        $vatAmount = $subtotal->multipliedBy($vatRate);
        $totalWithTax = $subtotal->plus($vatAmount);

        return Money::of(
            $totalWithTax->toFloat(),
            "SAR",
            roundingMode: \Brick\Math\RoundingMode::HALF_UP,
        );
    }

    public function payer(): User
    {
        return $this->customer->user;
    }
}
