<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('products')->insert([
            [
                'user_id' => 1,
                'name' => '腕時計',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'price' => 15000,
                'image_url' => 'products/Clock.jpg',
                'condition' => '良好',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => 'HDD',
                'description' => '高速で信頼性の高いハードディスク',
                'price' => 5000,
                'image_url' => 'products/Disk.jpg',
                'condition' => '目立った傷や汚れなし',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => '玉ねぎ3束',
                'description' => '新鮮な玉ねぎ3束のセット',
                'price' => 300,
                'image_url' => 'products/Onion.jpg',
                'condition' => 'やや傷や汚れあり',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => '革靴',
                'description' => 'クラシックなデザインの革靴',
                'price' => 4000,
                'image_url' => 'products/Shoes.jpg',
                'condition' => '状態が悪い',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => 'ノートPC',
                'description' => '高性能なノートパソコン',
                'price' => 45000,
                'image_url' => 'products/Laptop.jpg',
                'condition' => '良好',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => 'マイク',
                'description' => '高音質のレコーディング用マイク',
                'price' => 8000,
                'image_url' => 'products/Mic.jpg',
                'condition' => '目立った傷や汚れなし',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'user_id' => 1,
                'name' => 'ショルダーバッグ',
                'description' => 'おしゃれなショルダーバッグ',
                'price' => 3500,
                'image_url' => 'products/Bag.jpg',
                'condition' => 'やや傷や汚れあり',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => 'タンブラー',
                'description' => '使いやすいタンブラー',
                'price' => 500,
                'image_url' => 'products/Tumbler.jpg',
                'condition' => '状態が悪い',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => 'コーヒーミル',
                'description' => '手動のコーヒーミル',
                'price' => 4000,
                'image_url' => 'products/CoffeeGrinder.jpg',
                'condition' => '良好',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1,
                'name' => 'メイクセット',
                'description' => '便利なメイクアップセット',
                'price' => 2500,
                'image_url' => 'products/MakeUpSet.jpg',
                'condition' => '目立った傷や汚れなし',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('product_category')->insert([
            ['product_id' => 1, 'category_id' => 1],
            ['product_id' => 1, 'category_id' => 5],
            ['product_id' => 1, 'category_id' => 12],
            ['product_id' => 2, 'category_id' => 2],
            ['product_id' => 2, 'category_id' => 8],
            ['product_id' => 3, 'category_id' => 10],
            ['product_id' => 4, 'category_id' => 1],
            ['product_id' => 4, 'category_id' => 5],
            ['product_id' => 5, 'category_id' => 2],
            ['product_id' => 5, 'category_id' => 5],
            ['product_id' => 5, 'category_id' => 8],

            ['product_id' => 6, 'category_id' => 8],
            ['product_id' => 6, 'category_id' => 9],
            ['product_id' => 7, 'category_id' => 1],
            ['product_id' => 7, 'category_id' => 4],
            ['product_id' => 7, 'category_id' => 12],
            ['product_id' => 8, 'category_id' => 3],
            ['product_id' => 8, 'category_id' => 10],
            ['product_id' => 9, 'category_id' => 3],
            ['product_id' => 9, 'category_id' => 10],
            ['product_id' => 10, 'category_id' => 1],
            ['product_id' => 10, 'category_id' => 4],
            ['product_id' => 10, 'category_id' => 6],
        ]);
    }
}
