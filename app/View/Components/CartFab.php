<?php

namespace App\View\Components;

use App\Factories\CartFactory;
use App\Actions\CalculateCartTotal;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CartFab extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $cart = CartFactory::make();
        $cartTotal = app(CalculateCartTotal::class)->execute($cart);
        return view('components.cart-fab', compact('cart', 'cartTotal'));
    }
}
