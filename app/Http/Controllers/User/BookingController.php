<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\Booking;
use App\Http\Models\Setting;
use App\Http\Models\ChargerWallet;
use App\Http\Models\Charger;
use App\Http\Models\ChargingHistory;
use Validator;
use Carbon\Carbon;
use App\Http\Services\BookingService;
use App\Http\Services\WebHookService;
use App\Http\Services\WalletHistoryService;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(BookingService $booking_service,WebHookService $webhook_service, WalletHistoryService $wallet_history_service)
    {
        $this->booking_service = $booking_service;
        $this->webhook_service = $webhook_service;
        $this->wallet_history_service = $wallet_history_service;
    }

    public function storeBooking(Request $request)
    {
        $user_exist = auth('sanctum')->user();
        $success = false;
        $store_booking = (object)[];
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'charger_station_id' => 'required',
                'start_time' => 'required|date|date_format:Y-m-d H:i',
                'end_time' => 'required|date|after_or_equal:start_time|date_format:Y-m-d H:i',
                // 'minutes' => 'required',
                'unit_price' => 'required',
                //'pre_auth_charge' => 'required',
                'booking_type' => 'required',
                // 'estimated_amount'=>'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
            } else {
                $now = Carbon::now();
                $from = Carbon::parse($request->start_time);
                $to = Carbon::parse($request->end_time);
                $minutes = $to->diffInMinutes($from);
                
                // if(!$from->greaterThan($now)){
                //     $code = config('constant.UNSUCCESS');
                //     $msg = 'Start time must be greater than current time';
                // }else 
                if($minutes >= 90){
                    $code = config('constant.UNSUCCESS');
                    $msg = 'Maximum time for each booking should be 90 minutes';
                }else{
                    // Check Charger Station Time (Open or Close)
                    $check_charger_time = $this->booking_service->checkChargerTime($request->charger_station_id,$from->format('H:i:s'), $to->format('H:i:s'));

                    $check_charger_available = Charger::where('id',$request->charger_station_id)->first();
                    if($check_charger_available->status == 1){
                        $code = config('constant.UNSUCCESS');
                        $msg = 'Charger station is unavailable at this time';
                    }else{
                        if(!$check_charger_time){
                            $code = config('constant.UNSUCCESS');
                            $msg = 'Charger station is closed';
                        }else{
                            $isAnyoneHasBooking= $this->booking_service->isAnyoneHasBooking($request->charger_station_id, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'));

                            if($isAnyoneHasBooking && $request->charger_station_id == $isAnyoneHasBooking->charger_station_id){
                                $code = config('constant.UNSUCCESS');
                                $msg = 'Booking already exists for this time slot, Please select another slot';
                            }else{
                                $code = config('constant.SUCCESS');
                                $msg = 'Booking Created Successfully';
                                $data = [
                                    'charger_station_id' => $request->charger_station_id,
                                    'user_id' => $user_id,
                                    'start_time' => $from,
                                    'end_time' => $to,
                                    'minutes' => $minutes,
                                    'unit_price' => $request->unit_price,
                                    'pre_auth_charge' => !blank($request->pre_auth_charge) ? $request->pre_auth_charge : null,
                                    'booking_type' => $request->booking_type,
                                    'payment_status' => 'Pending',
                                    'estimated_amount'=>$request->estimated_amount,
                                    'payment_type' => $request->payment_type ? $request->payment_type : 0,
                                ];
                                \Log::channel('booking')->info('booking_parameter',['parameter'=>$request->all(), 'data' => $data]);
                                $success = true;
                                $result = $this->booking_service->storeBooking($data);
                                // if($result->booking_type == 'pre'){
                                //     $razorpay_order_id = $this->webhook_service->createOrderForPrePayment($result);
                                //     Booking::where('id',$result['id'])->update(['razorpay_pre_order_id'=>$razorpay_order_id]);    
                                // }
                            }
                        }
                    }
                    if (!blank($result)) {
                        $store_booking = Booking::find($result->id);
                    }
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $store_booking));
    }

    public function bookingAvailability(Request $request)
    {
        $user_exist = auth('sanctum')->user();
        $success = false;
        $store_booking = (object)[];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'charger_station_id' => 'required',
                'start_time' => 'required|date|date_format:Y-m-d H:i',
                'end_time' => 'required|date|after_or_equal:start_time|date_format:Y-m-d H:i',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
            } else {
                $now = Carbon::now();
                $from = Carbon::parse($request->start_time);
                $to = Carbon::parse($request->end_time);
                $minutes = $to->diffInMinutes($from);
                
                // if(!$from->greaterThan($now)){
                //     $code = config('constant.UNSUCCESS');
                //     $msg = 'Start time must be greater than current time';
                // }else 
                if($minutes >= 90){
                    $code = config('constant.UNSUCCESS');
                    $msg = 'Maximum time for each booking should be 90 minutes';
                }else{
                    // Check Charger Station Time (Open or Close)
                    $check_charger_time = $this->booking_service->checkChargerTime($request->charger_station_id,$from->format('H:i:s'), $to->format('H:i:s'));

                    $check_charger_available = Charger::where('id',$request->charger_station_id)->first();
                    if($check_charger_available->status == 1){
                        $code = config('constant.UNSUCCESS');
                        $msg = 'Charger station is unavailable at this time';
                    }else{
                        if(!$check_charger_time){
                            $code = config('constant.UNSUCCESS');
                            $msg = 'Charger station is closed';
                        }else{
                            $isAnyoneHasBooking= $this->booking_service->isAnyoneHasBooking($request->charger_station_id, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'));

                            if($isAnyoneHasBooking && $request->charger_station_id == $isAnyoneHasBooking->charger_station_id){
                                $code = config('constant.UNSUCCESS');
                                $msg = 'Booking already exists for this time slot, Please select another slot';
                            }else{
                                $code = config('constant.SUCCESS');
                                $msg = 'Booking slot available';
                                $success = true;
                            }
                        }
                    }
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $store_booking));
    }

    public function updatePrePaymentStatus(Request $request){
        $payment_success = $request->payment_success;
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required',
            'transaction_id' => 'required',
        ]);
        $code = config('constant.UNSUCCESS');
        $msg = "Not Found";
        $get_booking = [];
        $success = false;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $booking_id = $request->booking_id;
            $user_exist = auth('sanctum')->user();
            if (!blank($user_exist)) {
                $get_booking = Booking::where('id',$booking_id)->first();
                if($payment_success == "success"){
                    $data = [
                        'pre_auth_transaction_id' => $request->transaction_id,
                        'payment_status' => "Pre",
                    ];
                    $success = true;
                    $code = config('constant.SUCCESS');
                    $msg = "Booking Created Successfully";
                    $booking = Booking::where('id',$booking_id)->update($data);
                }else{
                    $code = config('constant.UNSUCCESS');
                    $msg = "Payment Fail";
                }
            }else{
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $get_booking));
    }

    public function updatePrePaymentStatusNew(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'charge' => 'required',
            'payment_method' => 'required', // 1- wallet, 2 - direct
            'payment_type' => 'required', // pre , direct
            'transaction_id' => 'required_if:payment_method,==,2',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $booking = (object)[];
            $success = false;
            $user_exist = auth('sanctum')->user();
            if (!blank($user_exist)) {
                $user_id = auth('sanctum')->user()->id;
                $booking_id = $request->booking_id;
                // $history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->get();
                           
                // Generate unique transaction ID if payment method is 1 and transaction ID is empty
                if($request->payment_method == 1 && empty($request->transaction_id)){
                    $request->transaction_id = uniqid('Wallet_'.$booking_id.'_');
                }                
                
                // wallet payment
                if($request->payment_method == 1){
                    // Payment type pre
                    if($request->payment_type == 'pre'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                            ->where(function ($q) {
                                $q->whereNotNull('pre_auth_charge')->whereNotNull('pre_auth_transaction_id');
                        })->first();
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                        } else {
                            $data = [
                                'pre_auth_charge' => $request->charge,
                                'pre_auth_transaction_id' => "Wallet_".$booking_id,
                                'payment_status' => 'Pre',
                            ];
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                        }
                    }
                    // Payment type Final
                    if($request->payment_type == 'direct'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                            ->where(function ($q) {
                                $q->whereNotNull('final_charge')->whereNotNull('final_transaction_id');
                        })->first();
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                        } else {
                            $data = [
                                'final_charge' => $request->charge,
                                'final_transaction_id' => "Wallet_".$booking_id,
                                'payment_status' => 'Completed',
                            ];
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                        }
                    }

                    // Update Charging History Total Amount
                    // if(!blank($history)){
                    //     $update_history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->update(['total_amount'=>$request->charge]);
                    // }

                    // Deduct From User Wallet
                    $get_wallet = ChargerWallet::where('user_id',$user_id)->first();
                    $amount = $get_wallet->amount - $request->charge;
                    $update_wallet_amount = $get_wallet->update(['amount' => $amount]);
                    $description = ucfirst($request->payment_type)." booking charge-".$booking_id;
                    $this->wallet_history_service->createDebitHistory($request->charge,$description, $request->transaction_id, source : 'wallet');
                }

                // direct payment
                if($request->payment_method == 2){
                    // Payment type pre
                    if($request->payment_type == 'pre'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                            ->where(function ($q) {
                                $q->whereNotNull('pre_auth_charge')->whereNotNull('pre_auth_transaction_id');
                        })->first();
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                        } else {
                            $data = [
                                'pre_auth_charge' => $request->charge,
                                'pre_auth_transaction_id' => $request->transaction_id,
                                'payment_status' => 'Pre',
                            ];
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                        }
                    }
                    // Payment type Final
                    if($request->payment_type == 'direct'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                            ->where(function ($q) {
                                $q->whereNotNull('final_charge')->whereNotNull('final_transaction_id');
                        })->first();
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                        } else {
                            $data = [
                                'final_charge' => $request->charge,
                                'final_transaction_id' => $request->transaction_id,
                                'payment_status' => 'Completed',
                            ];
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                        }
                    }

                    // Update Charging History Total Amount
                    // if(!blank($history)){
                    //     $update_history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->update(['total_amount'=>$request->charge]);
                    // }

                    // Deduct From User Wallet
                    $description = ucfirst($request->payment_type)." booking charge-".$booking_id;
                    $this->wallet_history_service->createDebitHistory($request->charge,$description, $request->transaction_id, source : 'direct');
                }
            }else{
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $booking));
    }

    public function getMyBookings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'longitude' => 'required',
            'latitude' => 'required',
        ]);
        $code = config('constant.UNSUCCESS');
        $msg = "Not Found";
        $result = [];
        $success = false;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $user_exist = auth('sanctum')->user();
            if (!blank($user_exist)) {
                $user_id = auth('sanctum')->user()->id;
                $check_booking = Booking::where('user_id', $user_id)->first();
                if (!blank($check_booking)) {
                    $result = $this->booking_service->myBooking($user_id, $request->longitude, $request->latitude, $request->current_time);
                    $success = true;
                    $code = config('constant.SUCCESS');
                    $msg = 'Get My Booking Successfully';
                } else {
                    $code = config('constant.UNSUCCESS');
                    $msg = 'No Booking Found';
                }
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
    }

    public function myBookingHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'longitude' => 'required',
            'latitude' => 'required',
        ]);
        $code = config('constant.UNSUCCESS');
        $msg = "Not Found";
        $result = [];
        $success = false;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $user_exist = auth('sanctum')->user();
            $per_page = $request->perPages ? $request->perPages : "5";
            if (!blank($user_exist)) {
                $user_id = $user_exist->id;
                $bookings = Booking::where('user_id', $user_id)->first();
                if (!blank($bookings)) {
                    $result = $this->booking_service->myBookingHistory($user_id, $request->longitude, $request->latitude, $request->current_time, $per_page);
                    $success = true;
                    $code = config('constant.SUCCESS');
                    $msg = 'Get My Booking History Successfully';
                } else {
                    $success = true;
                    $code = config('constant.UNSUCCESS');
                    $msg = 'No Booking History Found';
                }
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
    }

    public function generateFinalOrderId(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'charge' => 'required',
        ]);
        $success = false;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $booking_id = $request->booking_id;
            $check_booking = Booking::where('id',$booking_id)->whereNotNull('razorpay_final_order_id')->first();
            if(!blank($check_booking)){
                $code = config('constant.SUCCESS');
                $msg = "Final Payment Id Already Generated";
            }else{
                $data = [
                    'id' => $booking_id,
                    'price' => $request->charge
                ];
                $success = true;
                $razorpay_final_order_id = $this->webhook_service->createOrderForFinalPayment($data);
                $code = config('constant.SUCCESS');
                $msg = "Final Payment Order ID Generated";
                Booking::where('id',$booking_id)->update(['razorpay_final_order_id'=>$razorpay_final_order_id]);
                
            }
            $result = Booking::where('id',$booking_id)->first();
            return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
        }
    }

    public function paymentSuccess(Request $request)
    {
        $payment_success = $request->payment_success;
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'transaction_id' => 'required',
            'charge' => 'required',
        ]);
        $code = config('constant.UNSUCCESS');
        $msg = "Not Found";
        $success = false;
        $booking = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $user_exist = auth('sanctum')->user();
            if (!blank($user_exist)) {
                $user_id = auth('sanctum')->user()->id;
                if($payment_success == "success"){
                    $data = [
                        'user_id' => $user_id,
                        'booking_id' => $request->booking_id,
                        'charge' => $request->charge,
                        'transaction_id' => $request->transaction_id
                    ];
                    $check_booking = Booking::where('id', $data['booking_id'])->where('user_id', $data['user_id'])
                        ->where(function ($q) {
                            $q->whereNotNull('pre_auth_charge')->whereNotNull('pre_auth_transaction_id')->whereNotNull('final_charge')->whereNotNull('final_transaction_id');
                        })->get();
                    if (!blank($check_booking)) {
                        $success = true;
                        $code = config('constant.SUCCESS');
                        $msg = 'Payment Already Done';
                        $booking = $check_booking;
                    } else {
                        \Log::info("data ".$data);
                        $booking = $this->booking_service->paymentSuccessBooking($data);
                        \Log::info("booking ".$booking);
                        if (!blank($booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                        } else {
                            $code = config('constant.UNSUCCESS');
                            $msg = 'Booking Not Found';
                        }
                    }
                }else{
                    $code = config('constant.UNSUCCESS');
                    $msg = "Payment Fail";
                }
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
            return response(array('code' => $code, 'msg' => $msg, 'success' => $success, 'result' => $booking));
        }
    }

    public function varifiedBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required',
            'uuid' => 'required',
        ]);
        $code = config('constant.UNSUCCESS');
        $msg = "Not Found";
        $success = true;
        $result = [];
        $user_booking = false;
        $allow_charging = false;
        $is_charger_exist = false;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $user_exist = auth('sanctum')->user();
            $start_time = Carbon::parse($request->start_time)->format('Y-m-d H:i:s');
            $end_time = Carbon::parse(Carbon::parse($request->start_time)->addMinutes(30))->format('Y-m-d H:i:s');
            $charger_from = Carbon::parse($request->start_time);
            $charger_to = Carbon::parse(Carbon::parse($request->start_time)->addMinutes(30));
            if (!blank($user_exist)) {
                $user_id = auth('sanctum')->user()->id;
                $chargers = $this->booking_service->checkChargerByUUID($request->uuid);
                if ($chargers) {
                    // if($chargers->status == "1" || $chargers->status == "2"){
                    //     $status = $chargers->status == "1" ? "Charger is unavailable" : "Charger is busy";
                    //     $code = config('constant.UNSUCCESS');
                    //     $msg = $status;
                    //     $success = false;
                    //     $result['booking_data'] = (object)[];
                    //     $result['charger_data'] = (object)[];
                    //     $result['allow_charging'] = false;
                    //     $result['is_charger_exist'] = false;
                    //     $result['user_booking'] = false;
                    //     $result['futurebooking_time'] = null;
                    // }else{
                        // Check Charger Station Time (Open or Close)
                        $check_charger_time = $this->booking_service->checkChargerTime($chargers->id,$charger_from->format('H:i:s'), $charger_to->format('H:i:s'));
                        if(!$check_charger_time){
                            $code = config('constant.UNSUCCESS');
                            $msg = 'Charger station is closed';
                            $result['booking_data'] = (object)[];
                            $result['charger_data'] = (object)[];
                            $result['futurebooking_time'] = null;
                        }else{
                            $check_future_booking = $this->booking_service->checkFutureBooking($chargers->id,$start_time,$end_time);
                            if(!blank($check_future_booking)){
                                $code = config('constant.SUCCESS');
                                $msg = "Future Booking";
                                $result['user_booking'] = false;
                                $result['allow_charging'] = true;
                                $result['is_charger_exist'] = true;
                                $result['booking_data'] = $check_future_booking;
                                $result['charger_data'] = $chargers;
                                $result['futurebooking_time'] = Carbon::parse($check_future_booking->start_time)->format('g:i A');
                            }else{
                                $is_charger_exist = true;
                                $isAnyoneHasBooking= $this->booking_service->isAnyoneHasBooking($chargers->id, $start_time, $end_time);
                                if(($isAnyoneHasBooking && $isAnyoneHasBooking->user_id != $user_id) && $chargers->status == "1" || $chargers->status == "2"){
                                    $status = $chargers->status == "1" ? "Charger is unavailable" : "Charger is busy";
                                    $code = config('constant.UNSUCCESS');
                                    $msg = $status;
                                    $success = false;
                                    $result['booking_data'] = (object)[];
                                    $result['charger_data'] = (object)[];
                                    $result['allow_charging'] = false;
                                    $result['is_charger_exist'] = false;
                                    $result['user_booking'] = false;
                                    $result['futurebooking_time'] = null;
                                }else{
                                    // When user has booking of requested charger
                                    if($isAnyoneHasBooking && $isAnyoneHasBooking->user_id == $user_id && $isAnyoneHasBooking->charger_station_id == $chargers->id){
                                            $bookingDetails = $this->booking_service->getBookingDetailsByChargerId($isAnyoneHasBooking->charger_station_id,$start_time, $end_time);

                                            if($bookingDetails){
                                                //check for wallet balance incase of pre booking
                                                if($bookingDetails->booking_type == 'pre'){
                                                    $success = true;
                                                    $code = config('constant.SUCCESS');
                                                    $msg = "Wallet Data";
                                                    $charger_Wallet = ChargerWallet::where('user_id',$user_id)->first();
                                                    $get_setting_amount = Setting::where('name','minimum_wallet_charging_amount')->first();
                                                    if(!blank($get_setting_amount) && $charger_Wallet->amount < $get_setting_amount->updated_value){
                                                        $code = 215;
                                                        $msg = "Minimum ".$get_setting_amount->updated_value." Rs required in your wallet balance for the charging";
                                                        $success = false;
                                                        $result['user_booking'] = false;
                                                        $result['allow_charging'] = false;
                                                        $result['is_charger_exist'] = true;
                                                        $result['booking_data'] = (object)[];
                                                        $result['charger_data'] = (object)[];
                                                        $result['futurebooking_time'] = null;
                                                    }else {
                                                        $code = config('constant.SUCCESS');
                                                        $msg = "This user has booking at this time";
                
                                                        $result['user_booking'] = true;
                                                        $result['allow_charging'] = true;
                                                        $result['is_charger_exist'] = true;
                                                        $result['booking_data'] = $bookingDetails;
                                                        $result['charger_data'] = $chargers;
                                                        $result['futurebooking_time'] = null;
                                                    }                                              
                                                }
                                            }
                                            // When other user has booking of requested charger
                                        }else if($isAnyoneHasBooking && $isAnyoneHasBooking->user_id != $user_id && $isAnyoneHasBooking->charger_station_id == $chargers->id){
                                            $success = false;
                                            $code = config('constant.SUCCESS');
                                            $msg = "Another user has booking at this time";
                                            $varify_booking = $this->booking_service->getBookingDetailsByChargerId($isAnyoneHasBooking->charger_station_id,$start_time, $end_time);
        
                                            $result['user_booking'] = false;
                                            $result['allow_charging'] = false;
                                            $result['is_charger_exist'] = true;
                                            $result['booking_data'] = (object)[];
                                            $result['charger_data'] = (object)[];
                                            $result['futurebooking_time'] = null;
                                        }else{
                                            $user_exist = auth('sanctum')->user();
                                        
                                            $success = false;
                                            if (!blank($user_exist)) {
                                                $user_id = auth('sanctum')->user()->id;
                                                $success = true;
                                                $code = config('constant.SUCCESS');
                                                $msg = "Wallet Data";
                                                $charger_Wallet = ChargerWallet::where('user_id',$user_id)->first();
                                                $get_setting_amount = Setting::where('name','minimum_wallet_charging_amount')->first();
                                                if(!blank($get_setting_amount) && $charger_Wallet->amount < $get_setting_amount->updated_value){
                                                    $code = 215;
                                                    $msg = "Minimum ".$get_setting_amount->updated_value." Rs required in your wallet balance for the charging";
                                                    $success = false;
                                                    $result['user_booking'] = false;
                                                    $result['allow_charging'] = false;
                                                    $result['is_charger_exist'] = true;
                                                    $result['booking_data'] = (object)[];
                                                    $result['charger_data'] = (object)[];
                                                    $result['futurebooking_time'] = null;
                                                }
                                                else{
                                                    $code = config('constant.SUCCESS');
                                                    $msg = "No one has has booking at this time";
                                                    $result['user_booking'] = false;
                                                    $result['allow_charging'] = true;
                                                    $result['is_charger_exist'] = true;
                                                    $result['booking_data'] = (object)[];
                                                    $result['charger_data'] = $chargers;
                                                    $result['futurebooking_time'] = null;  
                                                } 
        
                                            }else{
                                                $code = config('constant.UNSUCCESS');
                                                $msg = "User Not Found";
                                                $result['user_booking'] = false;
                                                $result['allow_charging'] = true;
                                                $result['is_charger_exist'] = true;
                                                $result['booking_data'] = (object)[];
                                                $result['charger_data'] = $chargers;
                                                $result['futurebooking_time'] = null;  
                                            }
                                            // When no one has booking of requested charger  
                                        }
                                }
                                
                            }
                        }
                    // }
                } else {
                    $result['allow_charging'] = false;
                    $result['is_charger_exist'] = false;
                    $result['user_booking'] = false;
                    $result['booking_data'] = (object)[];
                    $result['charger_data'] = (object)[];
                    $result['futurebooking_time'] = null;
                    $code = config('constant.UNSUCCESS');
                    $msg = "Charger Not Found";
                }
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
            return response(array('code' => $code, 'msg' => $msg, 'success' => $success, 'result' => $result));
        }
    }

    public function refundUserAmount(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'refund_amount' => 'required',
            'charge' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Amount Added in User Wallet";
            $success = true;
            $user_id = auth('sanctum')->user()->id;
            $wallet_data = ChargerWallet::where('user_id',$user_id)->first();
            $amount = $wallet_data->amount + $request->refund_amount;
            $description = "Refund for remaining amount Rs.".$request->refund_amount;
            $this->wallet_history_service->createCreditHistory($request->refund_amount,$description, $request->transaction_id);
            $update_data = $wallet_data->update(['amount'=>$amount]);
            $update_history = ChargingHistory::where('booking_id',$request->booking_id)->where('user_id', $user_id)->update(['total_amount'=>$request->charge]);
            $update_booking = Booking::where('id',$request->booking_id)->update(['final_charge'=>$request->charge]);
            return response(array('code' => $code, 'msg' => $msg, 'success' => $success));
        }
    }

    public function paymentSuccessNew(Request $request){
        Log::channel('booking')->info('Payment process started', ['request_data' => $request->all()]);
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer',
            'charge' => 'required',
            'payment_method' => 'required', // 1- wallet, 2 - direct
            'payment_type' => 'required', // pre , direct
            'transaction_id' => 'required_if:payment_method,==,2',
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            Log::channel('booking')->error('Validation failed', ['error' => $error]);
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $error]);
        } else {
            $booking = (object)[];
            $success = false;
            $payment_success = false;
            $user_exist = auth('sanctum')->user();
            Log::channel('booking')->info('User authenticated', ['user' => $user_exist]);
            if (!blank($user_exist)) {
                $user_id = auth('sanctum')->user()->id;
                $booking_id = $request->booking_id;
                $history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->get();
                Log::channel('booking')->info('Charging history retrieved', ['history' => $history]);

                // wallet payment
                if($request->payment_method == 1){
                    Log::channel('booking')->info('Processing wallet payment', [
                        'payment_type' => $request->payment_type,
                        'charge' => $request->charge
                    ]);

                    // Payment type pre
                    if($request->payment_type == 'pre'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                        ->where(function ($q) {
                            $q->whereNotNull('pre_auth_charge')->whereNotNull('pre_auth_transaction_id');
                        })->first();
                        Log::channel('booking')->info('Pre-payment check', ['booking' => $check_booking]);
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                            Log::channel('booking')->info('Payment already done', ['booking' => $booking]);
                        } else {
                            $data = [
                                'pre_auth_charge' => $request->charge,
                                'pre_auth_transaction_id' => "Wallet_".$booking_id,
                                'payment_status' => 'Pre',
                            ];
                            Log::channel('booking')->info('Updating booking for pre-payment', ['data' => $data]);
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                        }
                    }
                    // Payment type Final
                    if($request->payment_type == 'direct'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                        ->where(function ($q) {
                            $q->whereNotNull('final_charge')->whereNotNull('final_transaction_id');
                        })->first();
                        Log::channel('booking')->info('Final payment check', ['booking' => $check_booking]);
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                            Log::channel('booking')->info('Payment already done', ['booking' => $booking]);
                        } else {
                            // Check if pre amount is greater then final amount then refund remaining amount
                            // $get_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->first();
                            // if(!blank($get_booking)){
                            //     if($get_booking->booking_type == "pre" && !blank($get_booking->pre_auth_charge)){
                            //         $total = ($get_booking->pre_auth_charge - $request->charge);
                            //         if($total > 0){
                            //             // Deduct From User Wallet
                            //             $get_wallet = ChargerWallet::where('user_id',$user_id)->first();
                            //             $amount = $get_wallet->amount + $total;
                            //             $update_wallet_amount = $get_wallet->update(['amount' => $amount]);
                            //             $description = "Refund for remaining amount Rs.".$total;
                            //             $this->wallet_history_service->createCreditHistory($total,$description);
                            //         }
                            //     }
                            // }
                            $data = [
                                'final_charge' => $request->charge,
                                'final_transaction_id' => "Wallet_".$booking_id,
                                'payment_status' => 'Completed',
                            ];

                            $check_booking = ChargerWallet::where('user_id',$user_id)->first();
                            
                            if($check_booking->amount < $request->charge){
                                $data['pending_amount'] = $request->charge - $check_booking->amount;
                            }
                           
                            $success = true;
                            $payment_success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                            Log::channel('booking')->info('Updating booking for final payment', ['data' => $data, 'booking_updated' => $update_booking]);
                        }
                    }

                    // Update Charging History Total Amount
                    if(!blank($history)){
                        $updateData = ['total_amount' => $request->charge];

                        if ($payment_success && $request->payment_type === 'direct') {
                            $updateData['payment_status'] = 'Completed';
                            $updateData['transaction_id'] = "Wallet_".$booking_id;
                        }
                        Log::channel('booking')->info('Updating charging history', ['updateData' => $updateData]);
                        $update_history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->update($updateData);
                        Log::channel('booking')->info('Charging history updated', ['result' => $update_history]);
                    }

                    // Deduct From User Wallet
                    $get_wallet = ChargerWallet::where('user_id',$user_id)->first();
                    $amount = (float)$get_wallet->amount - (float)$request->charge;
                    if((float)$amount > 0){
                        $amount = $amount;
                    }else{
                        $amount = 0;
                    }
                    if((float)$request->charge >= (float)$get_wallet->amount ){                        
                        $walletBal = $get_wallet->amount;
                    }else {                        
                        $walletBal = $request->charge;
                    }
                    Log::channel('booking')->info('Updating wallet balance', ['new_balance' => $amount]);
                    $update_wallet_amount = $get_wallet->update(['amount' => $amount]);
                    $description = ucfirst($request->payment_type)." booking charge-".$booking_id;
                    $this->wallet_history_service->createDebitHistory($walletBal,$description, "Wallet_".$booking_id, source : 'wallet');
                }

                // direct payment
                if($request->payment_method == 2){
                    Log::channel('booking')->info('Processing direct payment', [
                        'payment_type' => $request->payment_type,
                        'charge' => $request->charge
                    ]);
                    // Payment type pre
                    if($request->payment_type == 'pre'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                        ->where(function ($q) {
                            $q->whereNotNull('pre_auth_charge')->whereNotNull('pre_auth_transaction_id');
                        })->first();
                        Log::channel('booking')->info('Pre-payment check for direct payment', ['booking' => $check_booking]);
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                        } else {
                            $data = [
                                'pre_auth_charge' => $request->charge,
                                'pre_auth_transaction_id' => $request->transaction_id,
                                'payment_status' => 'Pre',
                            ];
                            Log::channel('booking')->info('Updating booking for pre-payment in direct payment', ['data' => $data]);
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                        }
                    }
                    // Payment type Final
                    if($request->payment_type == 'direct'){
                        $check_booking = Booking::where('id', $booking_id)->where('user_id', $user_id)
                        ->where(function ($q) {
                            $q->whereNotNull('final_charge')->whereNotNull('final_transaction_id');
                        })->first();
                        Log::channel('booking')->info('Final payment check for direct payment', ['booking' => $check_booking]);
                        if (!blank($check_booking)) {
                            $success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Already Done';
                            $booking = $check_booking;
                        } else {
                            $data = [
                                'final_charge' => $request->charge,
                                'final_transaction_id' => $request->transaction_id,
                                'payment_status' => 'Completed',
                            ];
                            $success = true;
                            $payment_success = true;
                            $code = config('constant.SUCCESS');
                            $msg = 'Payment Updated Successfully';
                            $update_booking = Booking::where('id',$booking_id)->where('user_id',$user_id)->update($data);
                            Log::channel('booking')->info('Updating booking for final payment in direct payment', ['data' => $data, 'booking_updated' => $update_booking]);
                        }
                    }

                    // Update Charging History Total Amount
                    if(!blank($history)){
                        $updateData = ['total_amount' => $request->charge];

                        if ($payment_success && $request->payment_type === 'direct') {
                            $updateData['payment_status'] = 'Completed';
                            $updateData['transaction_id'] = $request->transaction_id;
                        }
                        Log::channel('booking')->info('Updating charging history for direct payment', ['updateData' => $updateData]);
                        $update_history = ChargingHistory::where('booking_id',$booking_id)->where('user_id', $user_id)->update($updateData);
                        Log::channel('booking')->info('Charging history updated for direct payment', ['result' => $update_history]);
                    }
                    // Update User Wallet history 
                    $walletBal = $request->charge;
                    $description = ucfirst($request->payment_type)." booking charge -".$booking_id;
                    Log::channel('booking')->info('Recording Transaction for direct payment');
                    $this->wallet_history_service->createDebitPhonepeHistory($walletBal,$description, $request->transaction_id);
                    
                }
            }else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
                Log::channel('booking')->warning('User not found during payment process');
            }
            return response(array('code' => $code, 'msg' => $msg, 'success' => $success, 'result' => $booking));
        }
    }
}
