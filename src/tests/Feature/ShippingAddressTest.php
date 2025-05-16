<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\ShippingAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_送付先住所を変更すると購入画面に反映される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        ShippingAddress::create([
            'user_id' => $user->id,
            'post_code' => '000-0000',
            'address' => '初期住所',
            'building_name' => '初期建物名',
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('purchase.address.update', ['item_id' => $product->id]), [
            'post_code' => '123-4567',
            'address' => '東京都新宿区テスト1-2-3',
            'building_name' => 'テストビル101',
        ]);

        $response->assertRedirect(route('purchase.show', ['item_id' => $product->id]));

        $response = $this->get("/purchase/{$product->id}");

        $response->assertSee('123-4567');
        $response->assertSee('東京都新宿区テスト1-2-3');
        $response->assertSee('テストビル101');
    }

    public function test_購入した商品に送付先住所が紐づいて登録される()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        ShippingAddress::create([
            'user_id' => $user->id,
            'post_code' => '000-0000',
            'address' => '初期住所',
            'building_name' => '初期建物名',
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('purchase.address.update', ['item_id' => $product->id]), [
            'post_code' => '123-4567',
            'address' => '東京都新宿区テスト1-2-3',
            'building_name' => 'テストビル101',
        ]);

        $response = $this->post("/purchase/{$product->id}", [
            'payment_method' => 'カード払い',
        ]);

        $response->assertRedirect('/mypage?tab=buy');

        $this->assertDatabaseHas('shipping_addresses', [
            'user_id' => $user->id,
            'post_code' => '123-4567',
            'address' => '東京都新宿区テスト1-2-3',
            'building_name' => 'テストビル101',
        ]);
    }
}
