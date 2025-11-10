<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Observers\PaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy(PaymentObserver::class)]
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        "external_reference",
        "gateway",
        "status",
        "user_id",
        "payable_type",
        "payable_id",
        "details",
        "amount",
        "currency",
    ];

    protected function casts(): array
    {
        return [
            "status" => PaymentStatus::class,
            "gateway" => PaymentGateway::class,
            "details" => "array",
            "price" => MoneyCast::class,
            "money" => MoneyCast::class . ":amount,currency",
            "external_reference" => "string",
            "currency" => "string",
            "amount" => "decimal:2",
        ];
    }

    public function money(): Attribute
    {
        return Attribute::get(function () {
            return \Brick\Money\Money::of(
                $this->amount,
                $this->currency,
                roundingMode: \Brick\Math\RoundingMode::HALF_UP,
            );
        });
    }

    public function isPending(): Attribute
    {
        return Attribute::get(fn() => $this->status === PaymentStatus::pending);
    }

    public function isPaid(): Attribute
    {
        return Attribute::get(fn() => $this->status === PaymentStatus::paid);
    }

    public function isFailed(): Attribute
    {
        return Attribute::get(fn() => $this->status === PaymentStatus::failed);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
