<?php

namespace App\Casts;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    protected const currency = 'SAR';

    public function __construct(protected $column = 'amount')
    {
        //
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof Money) {
            return $value;
        }

        // Check for sale expiration if this is the sale_price cast
        if ($this->column === 'sale_amount' && isset($attributes['sale_end_date'])) {
            $saleEndDate = \Carbon\Carbon::parse($attributes['sale_end_date']);
            if ($saleEndDate->isPast()) {
                return null;
            }
        }

        $amount = $attributes[$this->column] ?? null;
        if (! $amount) {
            return null;
        }

        return Money::of(
            $amount,
            self::currency,
            roundingMode: RoundingMode::UP
        );
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // Handle null values gracefully
        if ($value === null) {
            return [
                $this->column => null,
            ];
        }

        if ($value instanceof Money) {
            return [
                $this->column => $value->getAmount(),
            ];
        }

        if (is_numeric($value)) {
            return [
                $this->column => (string) $value,
            ];
        }

        Log::error('The given value is not a valid money representation.', [
            'value' => $value,
            'attributes' => $attributes,
            'model_class' => get_class($model),
        ]);

        throw new InvalidArgumentException('The given value is not a valid money representation.');
    }
}
