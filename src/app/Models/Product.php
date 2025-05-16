<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'price', 'image_url', 'condition', 'brand'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }

    public function getIsSoldAttribute()
    {
        return $this->purchase()->exists();
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    // いいね済みかどうか判定する関数
    public function isFavoritedBy($user)
    {
        return $this->favorites->contains($user);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
