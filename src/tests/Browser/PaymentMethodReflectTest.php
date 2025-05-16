<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Product;
use App\Models\Address;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentMethodReflectTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function testPaymentMethodReflectsImmediately()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $user = User::factory()->create([
            'post_code' => '123-4567',
            'address' => '東京都テスト区1-2-3',
            'building_name' => 'テストビル101',
        ]);

        $this->browse(function (Browser $browser) use ($user, $product) {
            $browser->loginAs($user)
                ->visit("/purchase/{$product->id}")
                ->assertSee('支払い方法')

                // コンビニ払いを選択 → summary-methodに即時反映されるか
                ->select('#payment_method', 'コンビニ払い')
                ->pause(300)
                ->assertSeeIn('#summary-method', 'コンビニ払い')

                // カード払いを選択 → summary-methodが更新されるか
                ->select('#payment_method', 'カード払い')
                ->pause(300)
                ->assertSeeIn('#summary-method', 'カード払い');
        });
    }
}
