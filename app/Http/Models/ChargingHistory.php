<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChargingHistory extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'charging_history';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'charger_station_id',
        'user_id',
        'booking_id',
        'charging_start_time',
        'charging_end_time',
        'charging_unit',
        'total_amount'
    ];

    public function users(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function chargers(){
        return $this->belongsTo(Charger::class,'charger_station_id');
    }

    public function bookings(){
        return $this->belongsTo(Booking::class,'booking_id');
    }
}
