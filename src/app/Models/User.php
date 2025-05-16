<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'image_url', 'post_code', 'address', 'building_name'];

    public function products() {
        return $this->hasMany(Product::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function purchasedProducts()
    {
        return $this->belongsToMany(Product::class, 'purchases');
    }

    public function shippingAddress()
{
    return $this->hasOne(ShippingAddress::class);
}
}
