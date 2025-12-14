<?php

namespace App\Http\Controllers;

use App\Factories\CartFactory;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::search($request->search ?? '');

        if ($request->has('sort')) {
            $sort = $request->input('sort');
            if ($sort === 'name_asc') {
                $query->orderBy('name_'.app()->getLocale(), 'asc');
            } elseif ($sort === 'name_desc') {
                $query->orderBy('name_'.app()->getLocale(), 'desc');
            }
        }

        $products = $query->get();
        $cart = CartFactory::make();

        return view('products.index', compact('products', 'cart'));
    }

    public function show(string $language, Product $product)
    {
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();
        $cart = CartFactory::make();

        return view('products.show', compact('product', 'relatedProducts', 'cart'));
    }
}
