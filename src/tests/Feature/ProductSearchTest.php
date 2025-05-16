<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_商品名で部分一致検索ができる()
    {
        Product::factory()->create(['name' => 'Apple Watch']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);
        Product::factory()->create(['name' => 'Watch Strap']);

        $response = $this->get('/?search=Watch');

        $response->assertStatus(200);
        $response->assertSee('Apple Watch');
        $response->assertSee('Watch Strap');
        $response->assertDontSee('Samsung Galaxy');
    }

    public function test_検索状態がマイリストでも保持されている()
    {
        $user = User::factory()->create();

        // 「いいね」された商品だけ
        $likedProduct = Product::factory()->create(['name' => 'iPhone Case']);
        $unrelatedProduct = Product::factory()->create(['name' => 'Android Cable']);

        $user->favorites()->attach($likedProduct->id);

        // 「iPhone」で検索＋マイリストタブ
        $response = $this->actingAs($user)->get('/?tab=mylist&search=iPhone');

        $response->assertStatus(200);
        $response->assertSee('iPhone Case');
        $response->assertDontSee('Android Cable');
    }
}
