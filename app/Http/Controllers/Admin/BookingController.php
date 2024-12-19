<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\Booking;
use App\Http\Models\Charger;
use App\Http\Models\ChargerWallet;
use App\Http\Services\WalletHistoryService;
use Carbon\Carbon;
use Validator;
use DateTime;

class BookingController extends Controller
{
    public function __construct(WalletHistoryService $wallet_history_service)
    {
        $this->wallet_history_service = $wallet_history_service;
    }
    public function getBooking()
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Booking Data Successfully';
        $get_booking_data = Booking::with('users', 'chargers')->get();
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_booking_data));
    }

    public function getUpcomingBooking(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Upcoming Booking Data Successfully';
        $current_date = Carbon::now('Asia/Kolkata');
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $get_upcoming_booking = Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->where('bookings.start_time', ">=", $current_date)
            ->where('bookings.end_time', ">=", $current_date)
            ->where('bookings.is_cancel', 0)
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('chargers.name','LIKE', "%{$search}%")->orWhere('chargers.city','LIKE', "%{$search}%")->orWhere('bookings.final_charge','LIKE', "%{$search}%")->orWhere('bookings.final_charge','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        if (!blank($get_upcoming_booking)) {
            foreach ($get_upcoming_booking as $key => $booking) {
                $minute = $current_date->diffInMinutes($booking->start_time, true);
                $get_upcoming_booking[$key]->diff_min = $minute;
            }
        }
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_upcoming_booking));
    }

    public function getCompletedBooking(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Completed Booking Data Successfully';
        $current_date = Carbon::now('Asia/Kolkata');
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $get_completed_booking = Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city,
            CASE 
                WHEN chargers.type = 'BLE' THEN bookings.final_charge 
                WHEN chargers.type = 'OCPP' THEN charging_history.total_amount 
                ELSE NULL 
            END AS final_charge")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->leftJoin('charging_history', 'charging_history.booking_id', '=', 'bookings.id')
            ->where('bookings.start_time', "<=", $current_date)
            ->where('bookings.end_time', "<=", $current_date)
            ->where('bookings.is_cancel', 0)
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('chargers.name','LIKE', "%{$search}%")->orWhere('chargers.city','LIKE', "%{$search}%")->orWhere('bookings.final_charge','LIKE', "%{$search}%")->orWhere('bookings.final_transaction_id','LIKE', "%{$search}%")->orWhere('bookings.pre_auth_transaction_id','LIKE', "%{$search}%")->orWhere('bookings.pre_auth_charge','LIKE', "%{$search}%");
                }
            })
            ->where(function ($q) {
                $q->where('bookings.payment_status', 'Completed')
                  ->orWhere('charging_history.payment_status', 'Completed');
            })
            ->orderBy($sort_by, $order_by)
            ->distinct('bookings.id')
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_completed_booking));
    }

    public function getCancelBooking(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Cancel Booking Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $get_cancel_booking = Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->where('bookings.is_cancel', 1)
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('chargers.name','LIKE', "%{$search}%")->orWhere('chargers.city','LIKE', "%{$search}%")->orWhere('bookings.final_charge','LIKE', "%{$search}%")->orWhere('bookings.final_transaction_id','LIKE', "%{$search}%")->orWhere('bookings.pre_auth_transaction_id','LIKE', "%{$search}%")->orWhere('bookings.pre_auth_charge','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_cancel_booking));
    }

    public function updateUpcomingBookingStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'is_cancel' => 'required'
        ]);
        $success = false;
        $update_status = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $check_booking = Booking::where('id', $request->id)->first();
            if (!blank($check_booking)) {
                $current_time = Carbon::now('Asia/Kolkata');
                $datetime1 = new DateTime($check_booking->start_time);
                $datetime2 = new DateTime($current_time);
                $interval = $datetime2->diff($datetime1);
                $amount = 0;
                $percentage = 0;
                $charge_amount = 0;
                $refund_amount = 0;
                $date = ($interval->d * 24 + $interval->h).'.'.$interval->i;
                $get_charger_owner = Charger::where('id',$check_booking->charger_station_id)->first();
                if(!blank($get_charger_owner)){
                    $charger_wallet = ChargerWallet::where('user_id',$get_charger_owner->user_id)->first();
                    if(!blank($charger_wallet)){
                        $charge_amount = $check_booking->final_charge != null ? $check_booking->final_charge : $check_booking->pre_auth_charge;
                    }
                    if($date >= 2) {
                        if(!blank($charger_wallet)){
                            $refund_amount = $charge_amount;
                            $amount = $charger_wallet->amount + $refund_amount;
                            $percentage = 100;
                        }
                    }   
                    else {
                        if(!blank($charger_wallet)){
                            $refund_amount = ($charge_amount*50)/100;
                            $amount = $charger_wallet->amount + $refund_amount;
                            $percentage = 50;
                        }
                    }
                    if(!blank($charger_wallet)){
                        $update_wallet_amount = $charger_wallet->update(['amount' => $amount]);
                    }
                }
                $data = [
                    'is_cancel' => $request->is_cancel,
                    'cancelled_time' => $current_time,
                    'refund_amount' => $refund_amount,
                    'refund_percentage' => $percentage,
                    'cancellation_reason'=>$request->cancellation_reason
                ];
                $update_status = Booking::where('id', $request->id)->update($data);
                $code = config('constant.SUCCESS');
                $msg = 'Booking Cancel Successfully';
                $success = true;
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = 'Booking Not Found';
            }
            return response(array('code' => $code, 'msg' => $msg, 'success' => $success, 'result' => $update_status));
        }
    }

    public function updateUpcomingBookingStatusClone(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'is_cancel' => 'required'
        ]);
        $success = false;
        $update_status = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $check_booking = Booking::where('id', $request->id)->with('chargers')->whereHas('chargers')->first();
            \Log::info("check_booking ".$check_booking);
            if (!blank($check_booking)) {
                $current_time = Carbon::now('Asia/Kolkata');
                $datetime1 = new DateTime($check_booking->start_time);
                $datetime2 = new DateTime($current_time);
                $interval = $datetime2->diff($datetime1);
                $amount = 0;
                $percentage = 0;
                $charge_amount = 0;
                $refund_amount = 0;
                $c_owner_amount = 0;
                $date = ($interval->d * 24 + $interval->h).'.'.$interval->i;
                $charger_wallet = ChargerWallet::where('user_id',$check_booking->user_id)->first();
                $c_owner_wallet = ChargerWallet::where('user_id',$check_booking->chargers->user_id)->first();
                if(!blank($charger_wallet)){
                    $charge_amount = $check_booking->final_charge != null ? $check_booking->final_charge : $check_booking->pre_auth_charge;
                    if($date >= 2) {
                        \Log::info("date>2 ".$date);
                        if(!blank($charger_wallet)){
                            $refund_amount = $charge_amount;
                            $amount = $charger_wallet->amount + $refund_amount;
                            $percentage = 100;
                            $description = "Refund Intiated of Rs.".$refund_amount;
                            $this->wallet_history_service->createCreditHistoryForAdmin($refund_amount,$description,$check_booking->user_id, $request->transaction_id);
                            \Log::info("percentage ".$percentage);
                        }
                    }   
                    else {
                        if(!blank($charger_wallet)){
                            $refund_amount = ($charge_amount*50)/100;
                            $amount = $charger_wallet->amount + $refund_amount;
                            $c_owner_amount = $c_owner_wallet->amount + $refund_amount;
                            $percentage = 50;
                            $description = "Refund Intiated of Rs.".$refund_amount;
                            $this->wallet_history_service->createCreditHistoryForAdmin($refund_amount,$description,$check_booking->user_id, $request->transaction_id);
                            $this->wallet_history_service->createCreditHistoryForAdmin($refund_amount,$description,$check_booking->chargers->user_id, $request->transaction_id);
                            \Log::info("percentage ".$percentage);
                        }
                    }
                    if(!blank($charger_wallet)){
                        \Log::info("update_wallet_amount");
                        $update_wallet_amount = $charger_wallet->update(['amount' => $amount]);
                        if(!blank($c_owner_wallet) && $c_owner_amount > 0){
                            $update_c_owner_wallet = $c_owner_wallet->update(['c_owner_amount'=>$c_owner_amount]);
                        }
                    }
                }
                $data = [
                    'is_cancel' => $request->is_cancel,
                    'cancelled_time' => $current_time,
                    'refund_amount' => $refund_amount > 0 ? $refund_amount : 0,
                    'refund_percentage' => $percentage,
                    'cancellation_reason'=>$request->cancellation_reason
                ];
                $update_status = Booking::where('id', $request->id)->update($data);
                $code = config('constant.SUCCESS');
                $msg = 'Booking Cancel Successfully';
                $success = true; 
            }else {
                $code = config('constant.UNSUCCESS');
                $msg = 'Booking Not Found';
            }
            return response(array('code' => $code, 'msg' => $msg, 'success' => $success, 'result' => $update_status));
        }
    }

    public function getPreAuthPayment(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Pre Auth Payment Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $pre_auth_payment = Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('bookings.pre_auth_charge','LIKE', "%{$search}%")->orWhere('chargers.name','LIKE', "%{$search}%")->orWhere('chargers.city','LIKE', "%{$search}%")->orWhere('bookings.pre_auth_transaction_id','LIKE', "%{$search}%");
                }
            })
            ->where('payment_status', "Pre")
            ->whereNull('final_charge')
            ->whereNull('final_transaction_id')
            ->whereNotNull('pre_auth_charge')
            ->whereNotNull('pre_auth_transaction_id')
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $pre_auth_payment));
    }

    public function getCompletedPayment(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Completed Payment Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;

        $completed_payment = Booking::selectRaw("bookings.*, CONCAT(users.first_name,' ',users.last_name) AS users, chargers.name as station, chargers.city as city,
            CASE 
                WHEN chargers.type = 'BLE' THEN bookings.final_charge 
                WHEN chargers.type = 'OCPP' THEN charging_history.total_amount 
                ELSE NULL 
            END AS final_charge")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->leftJoin('charging_history', 'charging_history.booking_id', '=', 'bookings.id')
            ->where('bookings.payment_status', "Completed")->orWhere('charging_history.payment_status', "Completed")
            ->where(function($query) {
                $query->where(function($q) {
                // Case for booking_type = 'pre'
                    $q->where('booking_type', 'pre')
                    ->whereNotNull('pre_auth_charge')
                    ->whereNotNull('pre_auth_transaction_id')
                    ->where(function($qq) {
                        $qq->where(function($q1) {
                            // Sub-case for type = 'BLE'
                            $q1->where('chargers.type', 'BLE')
                                ->whereNotNull('final_charge')
                                ->whereNotNull('final_transaction_id');
                        })->orWhere(function($q2) {
                            // Sub-case for type = 'OCPP'
                            $q2->where('chargers.type', 'OCPP')
                                ->whereNotNull('charging_history.total_amount');
                        });
                    });
                })->orWhere(function($q) {
                    // Case for booking_type = 'direct'
                    $q->where('booking_type', 'direct')
                        ->where(function($qq) {
                            $qq->where(function($q1) {
                                // Sub-case for type = 'BLE'
                                $q1->where('chargers.type', 'BLE')
                                    ->whereNotNull('final_charge')
                                    ->whereNotNull('final_transaction_id');
                        })->orWhere(function($q2) {
                            // Sub-case for type = 'OCPP'
                            $q2->where('chargers.type', 'OCPP')
                                ->whereNotNull('charging_history.total_amount');
                        });
                    });
                });
            })
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('bookings.final_charge','LIKE', "%{$search}%")->orWhere('chargers.name','LIKE', "%{$search}%")->orWhere('chargers.city','LIKE', "%{$search}%")->orWhere('bookings.final_transaction_id','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        
        return response(array('code' => $code, 'msg' => $msg, 'result' => $completed_payment));
    }

    public function myBookings(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get My Booking Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $get_data = Booking::selectRaw("DISTINCT bookings.*, chargers.name as station,
            CASE 
                WHEN chargers.type = 'BLE' THEN bookings.payment_status 
                WHEN chargers.type = 'OCPP' THEN charging_history.payment_status 
                ELSE NULL 
            END AS payment_status")
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->leftJoin('charging_history', 'charging_history.booking_id', '=', 'bookings.id')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('bookings.id','LIKE', "%{$search}%")->orWhere('chargers.name','LIKE', "%{$search}%")->orWhere('chargers.city','LIKE', "%{$search}%");
                }
            })
            ->where('bookings.user_id', $request->user_id)->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_data));
    }

    public function getBookingInfo(Request $request){
        $code = config('constant.SUCCESS');
        $msg = 'Get Booking Data Successfully';
        $data = Booking::where('id',$request->id)->with('chargers','chargers.users','users','chargingHistory','paymentHistory')->first();
        return response(array('code' => $code, 'msg' => $msg, 'result' => $data));
    }
}
