<?php

namespace App\Support;

use App\Enums\Language;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use stdClass;

class PaymentMethod
{
    public string $id;

    public string $name;

    public string $code;

    public Money $serviceCharge;

    public Money $total;

    public string $image;

    /**
     * Create a new instance of PaymentMethod from MyFatoorah response.
     */
    public static function fromMyFatoorah(mixed $data): self
    {
        if ($data instanceof stdClass) {
            $data = (array) $data;
        }

        $paymentMethod = new self();
        $paymentMethod->id = $data["PaymentMethodId"];
        $paymentMethod->name =
            Language::tryFrom(app()->getLocale()) === Language::ar
                ? $data["PaymentMethodAr"]
                : $data["PaymentMethodEn"];
        $paymentMethod->code = $data["PaymentMethodCode"];
        $paymentMethod->serviceCharge = Money::of(
            $data["ServiceCharge"],
            $data["CurrencyIso"],
            roundingMode: RoundingMode::HALF_UP,
        );
        $paymentMethod->total = Money::of(
            $data["TotalAmount"],
            $data["CurrencyIso"],
            roundingMode: RoundingMode::HALF_UP,
        );
        $paymentMethod->image = $data["ImageUrl"];

        return $paymentMethod;
    }

    /**
     * Creates a new instance of PaymentMethod for Tabby.
     */
    public static function tabby(Money $price): self
    {
        $self = new self();
        $self->id = "tabby";
        $self->name = "Pay later with Tabby";
        $self->code = "tabby";
        $self->serviceCharge = Money::of(0, $price->getCurrency());
        $self->total = $price;
        $self->image =
            "https://www.pfgrowth.com/wp-content/uploads/2023/03/tabby-logo-1.png";

        return $self;
    }

    /**
     * Creates a new instance of PaymentMethod for HyperPay.
     */
    public static function hyperpay(Money $price): self
    {
        $self = new self();
        $self->id = "hyperpay";
        $self->name =
            Language::tryFrom(app()->getLocale()) === Language::ar
                ? "الدفع ببطاقة مدى، فيزا، ماستركارد"
                : "Pay with MADA, Visa, Mastercard";
        $self->code = "hyperpay";
        $self->serviceCharge = Money::of(0, $price->getCurrency());
        $self->total = $price;
        $self->image =
            "https://www.hyperpay.com/wp-content/themes/hyperpaycustomtheme/assets/logo.svg";

        return $self;
    }
}
