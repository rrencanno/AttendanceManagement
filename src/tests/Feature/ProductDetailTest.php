<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_必要な商品情報が表示される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'テスト商品',
            'brand' => 'テストブランド',
            'price' => 1234,
            'description' => 'これはテスト用の商品説明です。',
            'condition' => '良好',
            'image_url' => 'test-image.jpg',
        ]);

        $product->favorites()->attach($user->id);

        Comment::factory()->count(2)->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'コメントテスト',
        ]);

        $response = $this->get(route('item_show', ['id' => $product->id]));

        $response->assertStatus(200);
        $response->assertSee('テスト商品');
        $response->assertSee('テストブランド');
        $response->assertSee('1,234');
        $response->assertSee('これはテスト用の商品説明です。');
        $response->assertSee('良好');
        $response->assertSee('コメントテスト');
        $response->assertSee($user->name);
        $response->assertSee('test-image.jpg');
    }

    public function test_複数選択されたカテゴリが表示される()
    {
        $product = Product::factory()->create(['name' => 'カテゴリ商品']);
        $categories = collect([
            Category::factory()->create(['name' => 'カテゴリA']),
            Category::factory()->create(['name' => 'カテゴリB']),
            Category::factory()->create(['name' => 'カテゴリC']),
        ]);

        $product->categories()->attach($categories->pluck('id'));

        $response = $this->get(route('item_show', ['id' => $product->id]));

        $response->assertStatus(200);
        foreach ($categories as $category) {
            $response->assertSee($category->name);
        }
    }
}
