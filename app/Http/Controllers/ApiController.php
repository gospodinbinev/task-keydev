<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

class ApiController extends Controller
{
    public function getAllCategories() {
        $categories = Category::get();

        return response()->json($categories);
    }

    public function getAllProducts() {
        $products = Product::with('category')->with('colors')->with('materials')->paginate(20);
        
        return response()->json($products);
    }
}
