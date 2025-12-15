<?php

namespace App\Models;

use App\Observers\ProductQuestionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ProductQuestionObserver::class)]
class ProductQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question',
        'email',
        'answer',
        'product_id',
        'customer_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer',
            'customer_id' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isAnswered(): Attribute
    {
        return Attribute::get(fn (): bool => ! empty($this->answer));
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProductQuestionSubscription::class);
    }

    public function isCreatedByCurrentCustomer(): Attribute
    {
        return Attribute::get(function (): bool {
            if (! auth()->check() || ! auth()->user()->customer) {
                return false;
            }

            $user = auth()->user();

            return $user->customer->id === $this->customer_id
                || $user->email === $this->email;
        });
    }

    public function isSubscribedByCurrentCustomer(): Attribute
    {
        return Attribute::get(function (): bool {
            if (! auth()->check() || ! auth()->user()->customer) {
                return false;
            }

            $user = auth()->user();

            return $this->subscriptions()
                ->where('email', $user->email)
                ->exists();
        });
    }
}
