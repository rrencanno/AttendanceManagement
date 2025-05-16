<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_支払い方法選択画面で選択が即時反映される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->get("/purchase/{$product->id}");

        $response->assertStatus(200);

        $response->assertSee('コンビニ払い');
        $response->assertSee('カード払い');

        $postResponse = $this->post("/purchase/{$product->id}", [
            'payment_method' => 'カード払い',
        ]);

        $postResponse->assertRedirect('/mypage?tab=buy');
    }
}
