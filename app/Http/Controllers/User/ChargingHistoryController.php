<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\ChargingHistory;
use Carbon\Carbon;
use Validator;
use App\Http\Services\ChargingHistoryService;
use App\Http\Services\WebHookService;

class ChargingHistoryController extends Controller
{
    public function __construct(ChargingHistoryService $charging_history_service,WebHookService $webhook_service)
    {
        $this->charging_history_service = $charging_history_service;
        $this->webhook_service = $webhook_service;
    }

    public function storeChargingStartHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charger_station_id' => 'required',
            'booking_id' => 'required',
            'charging_start_time' => 'required',
            'charging_unit' => 'required'
        ]);
        $success = false;
        $store_history = (object)[];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $user_exist = auth('sanctum')->user();
            $booking_id = $request->booking_id;
            if(!blank($user_exist)){
                $check_history = ChargingHistory::where('booking_id',$booking_id)->where('charger_station_id',$request->charger_station_id)->where(function($q){
                    $q->whereNotNull('charging_start_time')->orWhereNotNull('charging_end_time');
                })->first();
                if(!blank($check_history)){
                    $code = config('constant.UNSUCCESS');
                    $msg = "Charging already start to this booking";
                    $store_history = $check_history; 
                }else{

                    // Check Booking Time Validation
                    $charging_start_time = Carbon::parse($request->charging_start_time);
                    $check_booking_time = $this->charging_history_service->checkBookingTime($booking_id,$charging_start_time->format('Y-m-d H:i:s'));

                    if(!blank($check_booking_time)){
                        $success = true;
                        $user_id = auth('sanctum')->user()->id;
                        $code = config('constant.SUCCESS');
                        $msg = 'Charging History Created Successfully';
                        $data = [
                            'charger_station_id' => $request->charger_station_id,
                            'user_id' => $user_id,
                            'booking_id' => $booking_id,
                            'charging_start_time' => $charging_start_time,
                            'charging_unit' => $request->charging_unit
                        ];
                        $store_history = $this->charging_history_service->storeChargingHistory($data);
                    }else{
                        $code = config('constant.UNSUCCESS');
                        $msg = "Please wait for some time.";
                    }
                }
            }else{
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
            return response(array('code' => $code, 'msg' => $msg,'success'=>$success, 'result' => $store_history));
        }
    }

    public function storeChargingStopHistory(Request $request)
    {
        $success = false;
        $validator = Validator::make($request->all(), [
            'charging_history_id' => 'required',
            'charging_end_time' => 'required',
            'charging_unit' => 'required'
        ]);
        $stop_store_history = [];
        \Log::channel('charging_history')->info('stop_charging_param',['request_param'=>$request->all()]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try{
                $user_exist = auth('sanctum')->user();
                if(!blank($user_exist)){
                    $user_id = auth('sanctum')->user()->id;
                    $code = config('constant.SUCCESS');
                    $msg = 'Stop Charging History Successfully';
                    $data = [
                        'charging_end_time' => Carbon::parse($request->charging_end_time),
                        'charging_unit' => $request->charging_unit,
                        'charging_status' => 'Stop'
                    ];
                    \Log::channel('charging_history')->info('stop_charging_param',['request_param'=>$request->all(), 'data' => $data]);
                    $success = true;
                    $stop_store_history = $this->charging_history_service->updateChargingHistory($request->charging_history_id, $data);
                }else{
                    $code = config('constant.UNSUCCESS');
                    $msg = "User Not Found";
                }
            }catch(\Exception $e){
                $code = config('constant.UNSUCCESS');
                $msg = $e->getMessage();
                \Log::channel('charging_history')->info('stop_charging_exception',['exception'=>$e->getMessage()]);
            }
            return response(array('code' => $code, 'msg' => $msg, 'success'=> $success,'result' => $stop_store_history));
            
        }
    }

    public function myChargingHistory(Request $request)
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
                $check_history = ChargingHistory::where('user_id', $user_id)->first();
                if (!blank($check_history)) {
                    $result = $this->charging_history_service->myChargingHistory($user_id, $request->longitude, $request->latitude);
                    $success = true;
                    $code = config('constant.SUCCESS');
                    $msg = 'Get My Charging History Successfully';
                } else {
                    $success = true;
                    $code = config('constant.UNSUCCESS');
                    $msg = 'No Charging History Found';
                }
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
    }
}
