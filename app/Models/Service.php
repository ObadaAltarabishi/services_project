<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'exchange_time',
        'exchange_with_category_id',
        'user_id',
        'category_id',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function exchangeWithCategory()
    {
        return $this->belongsTo(Category::class, 'exchange_with_category_id');
    }

    public function exchangeableServices()
    {
        return $this->hasMany(Service::class, 'exchange_with_category_id', 'category_id');
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
