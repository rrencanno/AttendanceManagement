<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーは商品にいいねできる()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post("/favorite/{$product->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_いいねアイコンは追加済みで色が変わる()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // いいね追加
        $this->actingAs($user)->post("/favorite/{$product->id}");

        $response = $this->actingAs($user)->get("/item/{$product->id}");

        $response->assertStatus(200);
        $this->assertStringContainsString('class="icon-btn favorited"', $response->getContent());
    }

    public function test_再度いいねを押すと解除できる()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // 最初にいいね
        $this->actingAs($user)->post("/favorite/{$product->id}");

        // もう一度押して解除
        $this->actingAs($user)->post("/favorite/{$product->id}");

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }
}
