<?php

namespace App\Enums;

enum OrderStatus: string
{
    case new = 'new';
    case processing = 'processing';
    case complete = 'complete';
    case refunded = 'refunded';
    case canceled = 'canceled';
    case unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::new => 'New',
            self::processing => 'Processing',
            self::complete => 'Complete',
            self::refunded => 'Refunded',
            self::canceled => 'Canceled',
            self::unknown => 'Unknown',
        };
    }

    public static function values(): array
    {
        return array_map(fn ($e) => $e->value, self::cases());
    }
}
