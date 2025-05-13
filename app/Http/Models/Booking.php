<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'bookings';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'charger_station_id',
        'user_id',
        'start_time',
        'end_time',
        'minutes',
        'is_cancel',
        'cancelled_time',
        'pre_auth_charge',
        'pre_auth_transaction_id',
        'final_charge',
        'final_transaction_id',
        'unit_price',
        'estimated_amount',
        'payment_status',
        'cancellation_reason',             /* Changed 30-5-2023 */
        'razorpay_pre_order_id',
        'razorpay_final_order_id',
        'booking_type',
        'refund_amount',
        'refund_percentage',
        'pending_amount',
    ];

    public function users(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function chargers(){
        return $this->belongsTo(Charger::class,'charger_station_id');
    }

    public function chargingHistory(){
        return $this->hasOne(ChargingHistory::class,'booking_id');
    }  

    public function paymentHistory(){
        return $this->hasOne(PaymentHistory::class,'booking_id');
    } 
}
