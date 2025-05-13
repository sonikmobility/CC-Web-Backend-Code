<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charger extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'chargers';
    protected $guarded = [];

    protected $appends = ['qr_code_image_path'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'uuid',
        'is_private',
        'name',
        'type',
        'address',
        'city',
        'zip_code',
        'latitude',
        'longitude',
        'start_time',
        'end_time',
        'image',
        'price',
        'status', // 0 - Available, 1 - Unavailable, 2 - Busy, 3 - Under Maintenance
        'charge_type',
        'charger_speed',
        'connector_type' // 0 - Available, 1 - Unavailable
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getQrCodeImagePathAttribute()
    {
        return (config('constant.storage_path') . 'QR/');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, "charger_station_id", "id");
    }

    public function chargingHistory()
    {
        return $this->hasMany(ChargingHistory::class, "charger_station_id", "id");
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucfirst($value);
    }
}
