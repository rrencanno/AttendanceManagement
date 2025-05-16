<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_プロフィールページでユーザー情報と商品一覧が表示される()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'image_url' => 'profiles/test.jpg',
        ]);

        $products = Product::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $purchases = Purchase::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/mypage?tab=sell');

        $response->assertStatus(200);

        $response->assertSee('テストユーザー');
        $response->assertSee('profiles/test.jpg');

        foreach ($products as $product) {
            $response->assertSee($product->name);
        }


        $response = $this->actingAs($user)->get('/mypage?tab=buy');

        foreach ($purchases as $purchase) {
            $response->assertSee((string) $purchase->id);
        }
    }
}
