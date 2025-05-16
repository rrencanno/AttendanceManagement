<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン済みのユーザーはコメントを送信できる()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post("/comment/{$product->id}", [
            'comment' => 'これはテストコメントです。',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'comment' => 'これはテストコメントです。',
        ]);
    }

    public function test_ログイン前のユーザーはコメントを送信できない()
    {
        $product = Product::factory()->create();

        $response = $this->post("/comment/{$product->id}", [
            'comment' => 'ゲストユーザーのコメント',
        ]);

        $response->assertRedirect('/login'); // 認証されていない場合はログインへリダイレクト
        $this->assertDatabaseMissing('comments', [
            'comment' => 'ゲストユーザーのコメント',
        ]);
    }

    public function test_コメントが入力されていない場合バリデーションエラーになる()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post("/comment/{$product->id}", [
            'comment' => '', // 空コメント
        ]);

        $response->assertSessionHasErrors(['comment']);
    }

    public function test_コメントが256文字以上の場合バリデーションエラーになる()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $longComment = str_repeat('あ', 256);

        $response = $this->actingAs($user)->post("/comment/{$product->id}", [
            'comment' => $longComment,
        ]);

        $response->assertSessionHasErrors(['comment']);
    }
}
