<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellingProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品出品画面から正しく商品が登録できる()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post('/sell', [
            'name' => 'テスト商品',
            'description' => 'これはテスト用の商品です。',
            'price' => 3000,
            'condition' => '良好',
            'categories' => [$category->name],
            'image' => UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseHas('products', [
            'name' => 'テスト商品',
            'description' => 'これはテスト用の商品です。',
            'price' => 3000,
            'condition' => '良好',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('product_category', [
            'product_id' => Product::first()->id,
            'category_id' => $category->id,
        ]);
    }
}
