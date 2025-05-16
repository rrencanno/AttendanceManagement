<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品を購入すると購入が完了する()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->post("/purchase/{$product->id}", [
                'payment_method' => 'カード払い',
            ]);

        $response->assertRedirect('/mypage?tab=buy');
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_購入済み商品は商品一覧でSOLDと表示される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post("/purchase/{$product->id}", [
            'payment_method' => 'カード払い',
        ]);

        $response = $this->get('/');
        $response->assertSeeText('SOLD');
    }

    public function test_購入した商品はプロフィール購入一覧に表示される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post("/purchase/{$product->id}", [
            'payment_method' => 'カード払い',
        ]);

        $response = $this->get('/mypage?tab=buy');
        $response->assertSee($product->name);
    }
}
