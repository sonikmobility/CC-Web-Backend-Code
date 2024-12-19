<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    public $timestamps = true;
    protected $table = 'payment_history';
    protected $guarded = [];

    public function bookings(){
        return $this->belongsTo(Booking::class,'booking_id');
    }
}
