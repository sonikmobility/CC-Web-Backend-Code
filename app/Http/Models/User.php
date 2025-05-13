<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $appends = ['profile_image_path', 'admin_profile_image_path', 'full_name','api_profile_image_path'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'profile_image',
        'provider_name',
        'provider_id',
        'mobile_number',
        'user_vehicle_id',
        'is_skip', // 0 - No Skip, 1 - Skip
        'register_mail', // 0 - Not Sent, 1 - Sent
        'minimum_wallet_amount'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function getProfileImagePathAttribute()
    {
        return (config('constant.storage_path') . 'User/');
    }

    public function getAdminProfileImagePathAttribute()
    {
        return (config('constant.storage_path') . 'Admin/');
    }

    public function getApiProfileImagePathAttribute()
    {
        return (config('constant.storage_path') . 'User/original/');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class, 'id', 'user_id');
    }

    public function userVehiclesModel()
    {
        return $this->belongsToMany(VehicleModel::class, 'user_vehicle');
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucfirst($value);
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucfirst($value);
    }

    public function userVehiclesMake()
    {
        return $this->belongsToMany(VehicleMake::class, 'user_vehicle');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class,"user_id", "id");
    }

    public function chargingHistory()
    {
        return $this->hasMany(ChargingHistory::class);
    }
}
