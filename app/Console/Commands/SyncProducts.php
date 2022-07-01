<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use App\Models\Color;
use App\Models\Material;
use App\Models\Product;

class SyncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncing products from API to DB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("Cron is working fine!");

        $response = Http::get('https://dummy-shop-api.keydev.eu/api/v1/products');
        $products = json_decode($response);
        $page = $products->page;
        $lastPage = $products->lastPage;

        for ($page; $page <= $lastPage; $page++) {
            $response = Http::get('https://dummy-shop-api.keydev.eu/api/v1/products', [
                'page' => $page,
            ]);
            $products = json_decode($response);

            foreach($products->data as $product) {
                if (!(Category::where('name', $product->product_department)->exists())) {
                    $category = new Category();
                    $category->name = $product->product_department;
                    $category->save();
                }

                if (!(Color::where('name', $product->product_color)->exists())) {
                    $color = new Color();
                    $color->name = $product->product_color;
                    $color->save();
                }

                if (!(Material::where('name', $product->product_material)->exists())) {
                    $material = new Material();
                    $material->name = $product->product_material;
                    $material->save();
                }

                if (!(Product::where('_id', $product->_id)->exists())) {
                    $newProduct = new Product();
                    $newProduct->image = $product->product_image_md;
                    $newProduct->_id = $product->_id;
                    $newProduct->type = $product->product_type;
                    $newProduct->name = $product->product_name;
                    
                    $category = Category::where('name', $product->product_department)->first();
                    $newProduct->category_id = $category->id;

                    $newProduct->stock = $product->product_stock;
                    $newProduct->price = $product->product_price;
                    $newProduct->ratings = $product->product_ratings;
                    $newProduct->sales = $product->product_sales;

                    $newProduct->save();

                    $color = Color::where('name', $product->product_color)->first();
                    $newProduct->colors()->attach($color);

                    $material = Material::where('name', $product->product_material)->first();
                    $newProduct->materials()->attach($material);
                    
                } else {

                    $existingProduct = Product::where('_id', $product->_id)->first();
                    $existingProduct->image = $product->product_image_md;
                    $existingProduct->_id = $product->_id;
                    $existingProduct->type = $product->product_type;
                    $existingProduct->name = $product->product_name;

                    $category = Category::where('name', $product->product_department)->first();
                    $existingProduct->category_id = $category->id;

                    $existingProduct->stock = $product->product_stock;
                    $existingProduct->price = $product->product_price;
                    $existingProduct->ratings = $product->product_ratings;
                    $existingProduct->sales = $product->product_sales;

                    $color = Color::where('name', $product->product_color)->first();
                    $existingProduct->colors()->sync($color);

                    $material = Material::where('name', $product->product_material)->first();
                    $existingProduct->materials()->sync($material);
                    
                    $existingProduct->save();

                }
            }
        }
    }
}
