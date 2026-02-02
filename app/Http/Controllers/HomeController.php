<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::with(['category', 'units'])
            ->where('is_active', true)
            ->whereHas('units', function ($query) {
                $query->where('status', 'available');
            })
            ->take(8)
            ->get();

        $categories = Category::withCount(['products' => function ($query) {
            $query->where('is_active', true);
        }])->get();

        return view('frontend.home', compact('featuredProducts', 'categories'));
    }
}