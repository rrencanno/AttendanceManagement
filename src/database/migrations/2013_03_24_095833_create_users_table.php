<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // 主キー (unsignedBigInteger, auto_increment)
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable(); // Fortifyでメール認証を使う場合はこれも必要
            $table->string('password');
            $table->boolean('is_admin')->default(false); // 管理者フラグ
            $table->rememberToken(); // remember_token カラム (VARCHAR(100), nullable)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
