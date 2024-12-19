<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\Charger;
use App\Http\Models\Booking;
use App\Http\Models\User;
use Carbon\Carbon;
use App\Http\Models\UserRole;

class DashboardController extends Controller
{
    public function getChargerCount(Request $request)
    {
        $get_charger_count = Charger::count();
        return $get_charger_count;
    }

    public function getBookingCount(Request $request)
    {
        // $current_date = Carbon::now('Asia/Kolkata');
        // return Booking::selectRaw("bookings.*")
        //     ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
        //     ->join('users', 'users.id', '=', 'bookings.user_id')
        //     ->where(function($q) use($current_date) {
        //         $q->where('bookings.start_time', ">=", $current_date)->where('bookings.end_time', ">=", $current_date);
        //     })
        //     ->orWhere(function($q) use($current_date) {
        //         $q->where('bookings.start_time', "<=", $current_date)->where('bookings.end_time', "<=", $current_date);
        //     })
        //     ->where('bookings.payment_status', 'Completed')
        //     ->orWhere('bookings.payment_status', 'Pre')
        //     ->orWhere('bookings.is_cancel', '0')
        //     ->whereNotNull('bookings.pre_auth_charge')
        //     ->whereNotNull('bookings.pre_auth_transaction_id')
        //     ->whereNotNull('bookings.final_charge')
        //     ->whereNotNull('bookings.final_transaction_id')
        //     ->where(function($q){
        //         $q->where('bookings.is_cancel',1)->orWhere('bookings.is_cancel',0);
        //     })
        //     ->count();

        $upcoming_count = $this->upComingCount();
        $complete_count = $this->completeCount();
        $cancel_count = $this->cancelCount();
        return $upcoming_count + $complete_count + $cancel_count;
    }

    public function upComingCount(){
        $current_date = Carbon::now('Asia/Kolkata');
        return Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->where('bookings.start_time', ">=", $current_date)
            ->where('bookings.end_time', ">=", $current_date)
            ->where('bookings.is_cancel', 0)
            ->count();
    }

    public function completeCount(){
        $current_date = Carbon::now('Asia/Kolkata');
        return Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->leftJoin('charging_history', 'charging_history.booking_id', '=', 'bookings.id')
            ->where('bookings.start_time', "<=", $current_date)
            ->where('bookings.end_time', "<=", $current_date)
            ->where('bookings.is_cancel', 0)
            ->where(function ($q) {
                $q->where('bookings.payment_status', 'Completed')
                  ->orWhere('charging_history.payment_status', 'Completed');
            })
            ->distinct('bookings.id')
            ->count();
    }

    public function cancelCount(){
        return Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->where('bookings.is_cancel', 1)
            ->count();
    }

    public function getUserCount(Request $request)
    {
        // $get_user_count = User::whereHas('roles',function($query){
        //     $query->where('role_id',1);
        // })->where('status',1)->count();
        $get_user_count = UserRole::where('role_id', 2)->whereHas('users', function ($query) {
            $query->where('status', 1);
        })->count();
        return $get_user_count;
    }

    public function getDataCount(Request $request)
    {
        // Charger Data
        $get_charger_current_week_count = Charger::whereBetween('created_at', [Carbon::now('Asia/Kolkata')->startOfWeek(), Carbon::now('Asia/Kolkata')->endOfWeek()])->count();
        $get_charger_last_week_count = Charger::whereBetween('created_at', [Carbon::now('Asia/Kolkata')->subWeek()->startOfWeek(), Carbon::now('Asia/Kolkata')->subWeek()->endOfWeek()])->count();
        $get_charger_this_month_count = Charger::whereMonth('created_at', Carbon::now('Asia/Kolkata')->month)->count();
        $get_charger_privious_month_count = Charger::whereBetween('created_at', [Carbon::now('Asia/Kolkata')->subMonth()->startOfMonth(), Carbon::now('Asia/Kolkata')->subMonth()->endOfMonth()])->count();

        // Booking Data
        $get_booking_current_week_count = Booking::whereBetween('created_at', [Carbon::now('Asia/Kolkata')->startOfWeek(), Carbon::now('Asia/Kolkata')->endOfWeek()])->count();
        $get_booking_last_week_count = Booking::whereBetween('created_at', [Carbon::now('Asia/Kolkata')->subWeek()->startOfWeek(), Carbon::now('Asia/Kolkata')->subWeek()->endOfWeek()])->count();
        $get_booking_this_month_count = Booking::whereMonth('created_at', Carbon::now('Asia/Kolkata')->month)->count();
        $get_booking_privious_month_count = Booking::whereBetween('created_at', [Carbon::now('Asia/Kolkata')->subMonth()->startOfMonth(), Carbon::now('Asia/Kolkata')->subMonth()->endOfMonth()])->count();

        // // User Data
        // $get_user_current_week_count = User::whereHas('roles',function($query){
        //     $query->where('role_id',1);
        // })->where('status',1)->whereBetween('created_at',[Carbon::now('Asia/Kolkata')->startOfWeek(), Carbon::now('Asia/Kolkata')->endOfWeek()])->count();
        // $get_user_last_week_count = User::whereHas('roles',function($query){
        //     $query->where('role_id',1);
        // })->where('status',1)->whereBetween('created_at',[Carbon::now('Asia/Kolkata')->subWeek()->startOfWeek(), Carbon::now('Asia/Kolkata')->subWeek()->endOfWeek()])->count();
        // $get_user_this_month_count = User::whereHas('roles',function($query){
        //     $query->where('role_id',1);
        // })->where('status',1)->whereMonth('created_at', Carbon::now('Asia/Kolkata')->month)->count();
        // $get_user_privious_month_count = User::whereHas('roles',function($query){
        //     $query->where('role_id',1);
        // })->where('status',1)->whereBetween('created_at',[Carbon::now('Asia/Kolkata')->subMonth()->startOfMonth(), Carbon::now('Asia/Kolkata')->subMonth()->endOfMonth()])->count();

        $get_user_current_week_count = UserRole::where('role_id', 2)->whereHas('users', function ($query) {
            $query->where('status', 1)->whereBetween('created_at', [Carbon::now('Asia/Kolkata')->startOfWeek(), Carbon::now('Asia/Kolkata')->endOfWeek()]);
        })->count();
        $get_user_last_week_count = UserRole::where('role_id', 2)->whereHas('users', function ($query) {
            $query->where('status', 1)->whereBetween('created_at', [Carbon::now('Asia/Kolkata')->subWeek()->startOfWeek(), Carbon::now('Asia/Kolkata')->subWeek()->endOfWeek()]);
        })->count();
        $get_user_this_month_count = UserRole::where('role_id', 2)->whereHas('users', function ($query) {
            $query->where('status', 1)->whereMonth('created_at', Carbon::now('Asia/Kolkata')->month);
        })->count();
        $get_user_privious_month_count = UserRole::where('role_id', 2)->whereHas('users', function ($query) {
            $query->where('status', 1)->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
        })->count();

        // Today Data
        $get_today_bookings = Booking::with('chargers')->whereDate('created_at', Carbon::today())->get();
        $get_today_chargers = Charger::whereDate('created_at', Carbon::today())->get();

        $total_pre_auth_payments = Booking::where('is_cancel',0)->sum('pre_auth_charge');

        $total_final_payments = Booking::where([['bookings.is_cancel',0],['chargers.type', 'BLE']])
                                        ->join('chargers','chargers.id','bookings.charger_station_id')
                                        ->sum('final_charge');
                                        
        $total_ocpp_payments = Booking::where([['bookings.is_cancel', 0],['chargers.type', 'OCPP']])
                                        ->join('chargers','chargers.id','bookings.charger_station_id')
                                        ->join('charging_history','bookings.id','charging_history.booking_id')
                                        ->sum('charging_history.total_amount');

        $total_final_payments += $total_ocpp_payments;

        $data = [
            'charger_current_week_count' => $get_charger_current_week_count,
            'charger_last_week_count' => $get_charger_last_week_count,
            'charger_this_month_count' => $get_charger_this_month_count,
            'charger_privious_month_count' => $get_charger_privious_month_count,
            'booking_current_week_count' => $get_booking_current_week_count,
            'booking_last_week_count' => $get_booking_last_week_count,
            'booking_this_month_count' => $get_booking_this_month_count,
            'booking_privious_month_count' => $get_booking_privious_month_count,
            'user_current_week_count' => $get_user_current_week_count,
            'user_last_week_count' => $get_user_last_week_count,
            'user_this_month_count' => $get_user_this_month_count,
            'user_privious_month_count' => $get_user_privious_month_count,
            'today_booking_data' => $get_today_bookings,
            'today_charger_data' => $get_today_chargers,
            'total_pre_auth_payment' => number_format($total_pre_auth_payments,2),
            'total_final_payment' => number_format($total_final_payments,2)
        ];
        return $data;
    }
}
