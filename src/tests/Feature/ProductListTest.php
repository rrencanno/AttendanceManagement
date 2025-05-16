<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\Purchase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    public function test_全商品を取得できる()
    {
        Product::factory()->count(3)->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeTextInOrder(
            Product::pluck('name')->toArray()
        );
    }

    public function test_購入済み商品はSOLDと表示される()
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();

        Purchase::factory()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->get('/');
        $response->assertSeeText('SOLD');
    }

    public function test_自分が出品した商品は表示されない()
    {
        $user = User::factory()->create();
        $ownProduct = Product::factory()->create(['user_id' => $user->id]);
        $otherProduct = Product::factory()->create(); // 他人の出品

        $this->actingAs($user);
        $response = $this->get('/');

        $response->assertDontSeeText($ownProduct->name);
        $response->assertSeeText($otherProduct->name);
    }
}
