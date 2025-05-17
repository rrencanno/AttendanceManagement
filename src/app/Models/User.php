<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; // メール認証を使う場合
// use Laravel\Sanctum\HasApiTokens; // API認証を使う場合
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash; // Hashファサードをuseする

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime', // メール認証を使う場合
        'is_admin' => 'boolean',
    ];

    /**
     * パスワードを自動的にハッシュ化するミューテータ
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    // リレーションシップ
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function appliedCorrectionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class, 'user_id');
    }
}
