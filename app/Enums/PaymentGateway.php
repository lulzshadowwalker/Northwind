<?php

namespace App\Enums;

use Closure;
use Filament\Support\Colors\Color;

enum PaymentGateway: string
{
    /**
     * @deprecated
     */
    case myfatoorah = 'myfatoorah'; // Deprecated - will be removed
    case tabby = 'tabby';
    case hyperpay = 'hyperpay';

    public function label(): string
    {
        return match ($this) {
            self::myfatoorah => 'MyFatoorah (Deprecated)',
            self::tabby => 'Tabby',
            self::hyperpay => 'HyperPay',
        };
    }

    public function icons(): string
    {
        return match ($this) {
            self::myfatoorah => 'heroicon-o-credit-card',
            self::tabby => 'heroicon-o-credit-card',
            self::hyperpay => 'heroicon-o-credit-card',
        };
    }

    public function color(): string|array|bool|Closure|null
    {
        return match ($this) {
            self::myfatoorah => Color::hex('#FFA500'),
            self::tabby => Color::hex('#32CD32'),
            self::hyperpay => Color::hex('#1E90FF'),
        };
    }

    public static function values(): array
    {
        return array_map(fn ($e) => $e->value, self::cases());
    }
}
