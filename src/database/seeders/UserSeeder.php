<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザー (1名作成)
        User::factory()->admin()->create([
            'name' => '管理者 太郎',
            'email' => 'admin@example.com',
            // passwordはfactoryで'password'が設定される
        ]);

        // 一般ユーザー (例: 10名作成)
        User::factory()->count(10)->create();

        // 特定のテスト用一般ユーザー (ログインしやすいように)
        User::factory()->create([
            'name' => '一般 花子',
            'email' => 'user1@example.com',
        ]);
        User::factory()->create([
            'name' => '一般 次郎',
            'email' => 'user2@example.com',
        ]);
    }
}
