<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'shipping_address_id' => null,
            'payment_method' => $this->faker->randomElement(['コンビニ払い', 'カード払い']),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
