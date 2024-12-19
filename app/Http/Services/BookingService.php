<?php

namespace App\Http\Services;

use App\Http\Models\Booking;
use App\Http\Models\Charger;
use App\Http\Models\ChargerWallet;
use App\Http\Models\ChargingHistory;
use Carbon\Carbon;

class BookingService
{
    public function storeBooking($data)
    {
        return Booking::create($data);
    }

    public function paymentSuccessBooking($data)
    {
        \Log::info("service_data".$data);
        $booking_id = $data['booking_id'];
        $user_id = $data['user_id'];
        $booking = Booking::where('id', $booking_id)->where('user_id', $user_id)->first();
        \Log::info("booking_data".$booking);
        if (!blank($booking)) {
            if($booking->booking_type == 'pre'){
                \Log::info("if");
                if (!blank($booking->pre_auth_transaction_id) && !blank($booking->pre_auth_charge) && ($booking->payment_status != "Completed") && ($booking->payment_status != null)) {
                    \Log::channel('payment')->info('payment_success_parameter',[ 'payment_status' => "Completed", 'parameter'=>$data]);
                    $history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->get();
                    $data = [
                        'final_charge' => $data['charge'],
                        'final_transaction_id' => $data['transaction_id'],
                        'payment_status' => 'Completed',
                    ];
                    \Log::info("data".$data);
                    $update_booking = $booking->update($data);
                    
                    // Wallet Amount Update
                    $get_charger_owner = Charger::where('id',$booking->charger_station_id)->first();
                    if(!blank($get_charger_owner)){
                        $charger_wallet = ChargerWallet::where('user_id',$get_charger_owner->user_id)->first();
                        if(!blank($charger_wallet)){
                            $amount = $charger_wallet->amount + $data['final_charge'];
                            $update_wallet_amount = $charger_wallet->where('user_id',$get_charger_owner->user_id)->update(['amount' => $amount]);
                        }
                    }
                    
                    // Update Charging History Total Amount
                    if(!blank($history)){
                        $update_history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->update(['total_amount'=>$data['final_charge']]);
                    }
                } else {
                    \Log::channel('payment')->info('payment_success_parameter',[ 'payment_status' => "Pre", 'parameter'=>$data]);
                    // $data = [
                    //     'pre_auth_charge' => $data['charge'],
                    //     'pre_auth_transaction_id' => $data['transaction_id'],
                    //     'payment_status' => 'Pre',
                    // ];
                    // $update_booking = $booking->update($data);
                }
            }else{
                \Log::info("else");
                if(($booking->payment_status != "Completed") && ($booking->payment_status != null)){
                    $history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->get();
                    $data = [
                        'final_charge' => $data['charge'],
                        'final_transaction_id' => $data['transaction_id'],
                        'payment_status' => 'Completed',
                    ];
                    \Log::info("data".$data);
                    $update_booking = $booking->update($data);
                    
                    // Wallet Amount Update
                    $get_charger_owner = Charger::where('id',$booking->charger_station_id)->first();
                    if(!blank($get_charger_owner)){
                        $charger_wallet = ChargerWallet::where('user_id',$get_charger_owner->user_id)->first();
                        if(!blank($charger_wallet)){
                            $amount = $charger_wallet->amount + $data['final_charge'];
                            $update_wallet_amount = $charger_wallet->where('user_id',$get_charger_owner->user_id)->update(['amount' => $amount]);
                        }
                    }
                    
                    // Update Charging History Total Amount
                    if(!blank($history)){
                        $update_history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->update(['total_amount'=>$data['final_charge']]);
                    }
                }
            } 
        }  
        return $booking;
    }

    public function myBooking($user_id, $longitude, $latitude, $time)
    {
        $start_time = $time;
        $qr_code_image_path = config('constant.storage_path') . 'QR/';
        $booking = Booking::selectRaw("bookings.*, chargers.name as charger_station_name, chargers.address as address, DATE_FORMAT(chargers.start_time, '%l:%i %p') as charger_start_time, DATE_FORMAT(chargers.end_time, '%l:%i %p') as charger_end_time, DATE_FORMAT(bookings.start_time, '%l:%i %p, %d %b,%Y') as start_time, DATE_FORMAT(bookings.end_time, '%l:%i %p, %d %b,%Y') as end_time, chargers.latitude as latitude, chargers.longitude as longitude ,CONCAT(ROUND(111.111 *
                DEGREES(ACOS(LEAST(1.0, COS(RADIANS(chargers.latitude))
                 * COS(RADIANS({$latitude}))
                 * COS(RADIANS(chargers.longitude - {$longitude}))
                 + SIN(RADIANS(chargers.latitude))
                 * SIN(RADIANS({$latitude}))))),2),' km') as distance,
            '$qr_code_image_path' as qr_code_image_path, chargers.image")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->where('bookings.user_id', $user_id)
            ->where('bookings.is_cancel', 0)
            ->whereNotNull('bookings.pre_auth_transaction_id')
            ->whereNotNull('bookings.pre_auth_charge')
            ->where('bookings.start_time', '>=', $start_time)
            ->where('bookings.end_time', '>=', $start_time)
            ->get();
        return $booking;
    }

    public function myBookingHistory($user_id, $longitude, $latitude, $time, $per_page)
    {
        // $start_time = Carbon::now('Asia/Kolkata');
        $start_time = $time;
        $qr_code_image_path = config('constant.storage_path') . 'QR/';
        $booking = Booking::selectRaw("bookings.*, chargers.name as charger_station_name, chargers.address as address, DATE_FORMAT(chargers.start_time, '%l:%i %p') as charger_start_time, DATE_FORMAT(chargers.end_time, '%l:%i %p') as charger_end_time, DATE_FORMAT(bookings.start_time, '%l:%i %p, %d %b,%Y') as start_time, DATE_FORMAT(bookings.end_time, '%l:%i %p, %d %b,%Y') as end_time, chargers.latitude as latitude, chargers.longitude as longitude ,CONCAT(ROUND(111.111 *
                DEGREES(ACOS(LEAST(1.0, COS(RADIANS(chargers.latitude))
                 * COS(RADIANS({$latitude}))
                 * COS(RADIANS(chargers.longitude - {$longitude}))
                 + SIN(RADIANS(chargers.latitude))
                 * SIN(RADIANS({$latitude}))))),2),' km') as distance,
            '$qr_code_image_path' as qr_code_image_path, chargers.image")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->where('bookings.user_id', $user_id)
            ->where('bookings.start_time', '<=', $start_time)
            ->where('bookings.end_time', '<=', $start_time)
            ->orderBy('bookings.start_time', 'desc')
            ->paginate($per_page);
        return $booking;
    }

    public function checkChargerByUUID($uuid)
    {
        return Charger::selectRaw("chargers.*,DATE_FORMAT(chargers.start_time, '%l:%i %p') as start_time, DATE_FORMAT(chargers.end_time, '%l:%i %p') as end_time, chargers.price as unit_price")
            ->where('uuid', $uuid)->first();
    }

    public function isAnyoneHasBooking($charger_id, $start_time, $end_time)
    {
        return \DB::table('bookings')->where(
            fn ($q) => $q->whereBetween('start_time', [$start_time, $end_time])
            ->orWhereBetween('end_time', [$start_time, $end_time])
            ->orWhere(
                fn ($q) => $q->where('start_time', '<', $start_time)
                    ->where('end_time', '>', $end_time)
                )
            )
            ->where('charger_station_id',$charger_id)
            ->whereNull('cancelled_time')
            ->where('is_cancel',0)
            ->whereNotNull('pre_auth_transaction_id')
            ->first();
    }

    public function checkFutureBooking($charger_id, $start_time, $end_time){
       return Booking::whereBetween('start_time', [$start_time, $end_time])->where('charger_station_id',$charger_id)->whereNull('cancelled_time')->where('is_cancel',0)->orderBy('start_time','ASC')->first();
    }

    public function checkChargerTime($charger_id,$start_time, $end_time){
        return Charger::selectRaw("chargers.*,TIME(chargers.start_time) as start_time, TIME(chargers.end_time) as end_time")
        ->where(function($q) use($start_time, $end_time){
            $q->whereRaw("TIME(chargers.start_time) < '$start_time'")->whereRaw("TIME(chargers.end_time) > '$end_time'");
        })
        ->where('chargers.id',$charger_id)
        ->first();
    }

    public function isUserHasBooking($charger_id, $user_id, $start_time, $end_time)
    {
        \DB::connection()->enableQueryLog();
        $existing = \DB::table('bookings')->where(
            fn ($q) => $q->whereBetween('start_time', [$start_time, $end_time])
                ->orWhereBetween('end_time', [$start_time, $end_time])
                ->orWhere(
                    fn ($q) => $q->where('start_time', '<', $start_time)
                        ->where('end_time', '>', $end_time)
                )
            )
            ->where('charger_station_id',$charger_id)
            ->where('user_id',$user_id)
            ->first();
        return $existing;
     
    }

    public function getBookingDetails($charger_id, $user_id)
    {
        return Booking::selectRaw("bookings.*, chargers.name as charger_station_name, chargers.address as address,DATE_FORMAT(bookings.start_time, '%l:%i %p, %d %b,%Y') as start_time, DATE_FORMAT(bookings.end_time, '%l:%i %p, %d %b,%Y') as end_time")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->where('bookings.charger_station_id', $charger_id)
            ->where('bookings.user_id',$user_id)
            ->first();
    }
    
    public function getBookingDetailsByChargerId($charger_id,$start_time,$end_time)
    {
        $booking = Booking::selectRaw("bookings.*, chargers.name as charger_station_name, chargers.address as address,DATE_FORMAT(bookings.start_time, '%l:%i %p, %d %b,%Y') as start_time, DATE_FORMAT(bookings.end_time, '%l:%i %p, %d %b,%Y') as end_time")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->where('bookings.charger_station_id', $charger_id)
            ->where(
            fn ($q) => $q->whereBetween('bookings.start_time', [$start_time, $end_time])
            ->orWhereBetween('bookings.end_time', [$start_time, $end_time])
            ->orWhere(
                fn ($q) => $q->where('bookings.start_time', '<', $start_time)
                    ->where('bookings.end_time', '>', $end_time)
                )
            )
            ->first();

            $booking->start_time = Carbon::createFromFormat('h:i A, d M,Y', $booking->start_time)->format('Y-m-d H:i:s');
            $booking->end_time = Carbon::createFromFormat('h:i A, d M,Y', $booking->end_time)->format('Y-m-d H:i:s');
            
            return $booking;
    }

    public function varifyBookings($charger_id, $user_id, $start_time, $end_time)
    {
        $check_booking = Booking::selectRaw("bookings.*, chargers.name as charger_station_name, chargers.address as address,DATE_FORMAT(bookings.start_time, '%l:%i %p, %d %b,%Y') as start_time, DATE_FORMAT(bookings.end_time, '%l:%i %p, %d %b,%Y') as end_time")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->where('bookings.charger_station_id', $charger_id)
            ->where('bookings.user_id',$user_id)
            ->where(function ($q) use ($start_time, $end_time) {
                $q->whereBetween('bookings.start_time', [$start_time, $end_time])->orWhereBetween('bookings.end_time', [$start_time, $end_time]);
            })
            ->first();
        if (!blank($check_booking)) {
            return $check_booking;
        } else {
            return $check_booking;
        }
    }
}
