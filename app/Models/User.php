<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

protected $fillable = [
           'name',
           'email',
           'role',
           'password',
           'report_count',
           'phone_number',
           'verification_code',
           'verification_code_sent_at'
];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // العلاقات
    public function wallet()
    {
        return $this->hasOne(wallet::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function files()
    {
        return $this->hasMany(File::class, 'uploader_id');
    }
    public function isAdmin()
    {
        return $this->role === 'admin'; // Simple check if role is 'admin'
    }
    
}