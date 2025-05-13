<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\AnalyticsService;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use App\Http\Models\ChargingHistory;

class AnalyticsController extends Controller
{
    public function __construct(AnalyticsService $analytics_service)
    {
        $this->analytics_service = $analytics_service;
    }

    public function myAnalytics(Request $request){
        $type = $request->type;
        $success = false;
        $time_period = $request->time_period;
        $user_exist = auth('sanctum')->user();
        if (!blank($user_exist)) 
        {
            $user_id = auth('sanctum')->user()->id;
            if($type == "unit"){
                if($time_period == "24 hours"){
                    $monthDates = [];
                    for($i = 1; $i <= 23; $i++){
                        $date = Carbon::now('Asia/Kolkata');
                        $start_of_day = Carbon::parse($date->startOfDay()->addHours($i));
                        $end_of_day = Carbon::parse($date->endOfDay());
                        $monthDates[] = [
                            'x' => $i,
                            'start_date' =>  Carbon::parse($start_of_day)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($start_of_day->addMinutes(59)->addSeconds(59))->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "30 days"){
                    $monthDates = [];
                    $date = Carbon::now('Asia/Kolkata');
                    $start_month = $date->startOfMonth()->format('m');
                    $end_of_month = $date->endOfMonth()->format('d');
                    for($i = 1; $i <= 30; $i++){
                        $start_date = Carbon::now('Asia/Kolkata')->startOfDay($i)->subDays($i);
                        $end_date = Carbon::now('Asia/Kolkata')->endOfDay($i)->subDays($i);
                        $monthDates[] = [
                            'x' => $start_date->format("d"),
                            'start_date' =>  Carbon::parse($start_date)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_date)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "1 year"){
                    $monthDates = [];
                    for($i = 1; $i <= 12; $i++){
                        $start_of_month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $end_of_month = $month->endOfMonth($i);
                        $monthDates[] = [
                            'x' => $month->shortMonthName,
                            'start_date' =>  Carbon::parse($start_of_month)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_of_month)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                else{
                    $monthDates = [
                        'code' => "101",
                        'msg' => "Please Enter Valid Time Period"
                    ];
                    return $monthDates;
                }
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Get Unit Wise Data SuccessFully";
                $monthDates = $this->analytics_service->getUnitWiseMyAnalytics($user_id,$monthDates);
                $collection = collect($monthDates);
                $all_unit = $collection->sum('total_unit');
                $all_amount = $collection->sum('total_amount');
                $all_charger = $collection->sum('total_charger');
                $all_seconds = $collection->sum(function ($date) {
                    $original_date = $date['total_hours'];
                    $parsed  = date_parse($original_date);
                    $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                    return $seconds;
                });
                $all_hour = CarbonInterval::seconds($all_seconds)->cascade()->forHumans(['short' => true]);
                if($all_hour == "1s"){
                    $all_hour = 0;
                }
                $final_data = [
                    'all_units' => $all_unit.' kWh',
                    'all_amount' => $all_amount,
                    'all_charger' => $all_charger,
                    'all_hour' => strtoupper($all_hour),
                    'month_data' => $monthDates
                ];
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $final_data));
            }
            elseif($type == "price")
            {
                if($time_period == "24 hours"){
                    $monthDates = [];
                    for($i = 1; $i <= 23; $i++){
                        $date = Carbon::now('Asia/Kolkata');
                        $start_of_day = Carbon::parse($date->startOfDay()->addHours($i));
                        $end_of_day = Carbon::parse($date->endOfDay());
                        $monthDates[] = [
                            'x' => $i,
                            'start_date' =>  Carbon::parse($start_of_day)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($start_of_day->addMinutes(59)->addSeconds(59))->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "30 days"){
                    $monthDates = [];
                    $date = Carbon::now('Asia/Kolkata');
                    $start_month = $date->startOfMonth()->format('m');
                    $end_of_month = $date->endOfMonth()->format('d');
                    for($i = 1; $i <= 30; $i++){
                        $start_date = Carbon::now('Asia/Kolkata')->startOfDay($i)->subDays($i);
                        $end_date = Carbon::now('Asia/Kolkata')->endOfDay($i)->subDays($i);
                        $monthDates[] = [
                            'x' => $start_date->format("d"),
                            'start_date' =>  Carbon::parse($start_date)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_date)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "1 year"){
                    $monthDates = [];
                    for($i = 1; $i <= 12; $i++){
                        $start_of_month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $end_of_month = $month->endOfMonth($i);
                        $monthDates[] = [
                            'x' => $month->shortMonthName,
                            'start_date' =>  Carbon::parse($start_of_month)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_of_month)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                else{
                    $monthDates = [
                        'code' => "101",
                        'msg' => "Please Enter Valid Time Period"
                    ];
                    return $monthDates;
                }
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Get Price Wise Data SuccessFully";
                $monthDates = $this->analytics_service->getPriceWiseMyAnalytics($user_id,$monthDates);
                $collection = collect($monthDates);
                $all_unit = $collection->sum('total_unit');
                $all_amount = $collection->sum('total_amount');
                $all_charger = $collection->sum('total_charger');
                $all_seconds = $collection->sum(function ($date) {
                    $original_date = $date['total_hours'];
                    $parsed  = date_parse($original_date);
                    $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                    return $seconds;
                });
                $all_hour = CarbonInterval::seconds($all_seconds)->cascade()->forHumans(['short' => true]);
                if($all_hour == "1s"){
                    $all_hour = 0;
                }
                $final_data = [
                    'all_units' => $all_unit.' kWh',
                    'all_amount' => $all_amount,
                    'all_charger' => $all_charger,
                    'all_hour' => strtoupper($all_hour),
                    'month_data' => $monthDates
                ];
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $final_data));
            }
            elseif($type == "hourly")
            {
                if($time_period == "24 hours"){
                    $monthDates = [];
                    for($i = 1; $i <= 23; $i++){
                        $date = Carbon::now('Asia/Kolkata');
                        $start_of_day = Carbon::parse($date->startOfDay()->addHours($i));
                        $end_of_day = Carbon::parse($date->endOfDay());
                        $monthDates[] = [
                            'x' => $i,
                            'start_date' =>  Carbon::parse($start_of_day)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($start_of_day->addMinutes(59)->addSeconds(59))->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "30 days"){
                    $monthDates = [];
                    $date = Carbon::now('Asia/Kolkata');
                    $start_month = $date->startOfMonth()->format('m');
                    $end_of_month = $date->endOfMonth()->format('d');
                    for($i = 1; $i <= 30; $i++){
                        $start_date = Carbon::now('Asia/Kolkata')->startOfDay($i)->subDays($i);
                        $end_date = Carbon::now('Asia/Kolkata')->endOfDay($i)->subDays($i);
                        $monthDates[] = [
                            'x' => $start_date->format("d"),
                            'start_date' =>  Carbon::parse($start_date)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_date)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "1 year"){
                    $monthDates = [];
                    for($i = 1; $i <= 12; $i++){
                        $start_of_month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $end_of_month = $month->endOfMonth($i);
                        $monthDates[] = [
                            'x' => $month->shortMonthName,
                            'start_date' =>  Carbon::parse($start_of_month)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_of_month)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                else{
                    $monthDates = [
                        'code' => "101",
                        'msg' => "Please Enter Valid Time Period"
                    ];
                    return $monthDates;
                }
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Get Hour Wise Data SuccessFully";
                $monthDates = $this->analytics_service->getHourWiseMyAnalytics($user_id,$monthDates);
                $collection = collect($monthDates);
                $all_unit = $collection->sum('total_unit');
                $all_amount = $collection->sum('total_amount');
                $all_charger = $collection->sum('total_charger');
                $all_seconds = $collection->sum(function ($date) {
                    $original_date = $date['total_hours'];
                    $parsed  = date_parse($original_date);
                    $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                    return $seconds;
                });
                $all_hour = CarbonInterval::seconds($all_seconds)->cascade()->forHumans(['short' => true]);
                if($all_hour == "1s"){
                    $all_hour = 0;
                }
                $final_data = [
                    'all_units' => $all_unit.' kWh',
                    'all_amount' => $all_amount,
                    'all_charger' => $all_charger,
                    'all_hour' => strtoupper($all_hour),
                    'month_data' => $monthDates
                ];
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $final_data));
            }
            else
            {
                $monthDates = [
                    'code' => "101",
                    'msg' => "Please Enter Valid Type"
                ];
                return $monthDates;
            }
        }else{
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
            return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
        }
    }

    public function myChargerAnalytics(Request $request){
        $type = $request->type;
        $time_period = $request->time_period;
        $charger_station_id = $request->charger_id;
        $success = false;
        $user_exist = auth('sanctum')->user();
        if (!blank($user_exist)) 
        {
            $user_id = auth('sanctum')->user()->id;
            if($type == "unit"){
                if($time_period == "24 hours"){
                    $monthDates = [];
                    for($i = 1; $i <= 23; $i++){
                        $date = Carbon::now('Asia/Kolkata');
                        $start_of_day = Carbon::parse($date->startOfDay()->addHours($i));
                        $end_of_day = Carbon::parse($date->endOfDay());
                        $monthDates[] = [
                            'x' => $i,
                            'start_date' =>  Carbon::parse($start_of_day)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($start_of_day->addMinutes(59)->addSeconds(59))->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "30 days"){
                    $monthDates = [];
                    $date = Carbon::now('Asia/Kolkata');
                    $start_month = $date->startOfMonth()->format('m');
                    $end_of_month = $date->endOfMonth()->format('d');
                    for($i = 1; $i <= 30; $i++){
                        $start_date = Carbon::now('Asia/Kolkata')->startOfDay($i)->subDays($i);
                        $end_date = Carbon::now('Asia/Kolkata')->endOfDay($i)->subDays($i);
                        $monthDates[] = [
                            'x' => $start_date->format("d"),
                            'start_date' =>  Carbon::parse($start_date)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_date)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "1 year"){
                    $monthDates = [];
                    for($i = 1; $i <= 12; $i++){
                        $start_of_month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $end_of_month = $month->endOfMonth($i);
                        $monthDates[] = [
                            'x' => $month->shortMonthName,
                            'start_date' =>  Carbon::parse($start_of_month)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_of_month)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                else{
                    $monthDates = [
                        'code' => "101",
                        'msg' => "Please Enter Valid Time Period"
                    ];
                    return $monthDates;
                }
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Get Unit Wise Data SuccessFully";
                $monthDates = $this->analytics_service->getUnitWiseMyChargerAnalytics($user_id,$charger_station_id,$monthDates);
                $collection = collect($monthDates);
                $all_unit = $collection->sum('total_unit');
                $all_amount = $collection->sum('total_amount');
                $all_charger = $collection->sum('total_charger');
                $all_seconds = $collection->sum(function ($date) {
                    $original_date = $date['total_hours'];
                    $parsed  = date_parse($original_date);
                    $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                    return $seconds;
                });
                $all_hour = CarbonInterval::seconds($all_seconds)->cascade()->forHumans(['short' => true]);
                if($all_hour == "1s"){
                    $all_hour = 0;
                }
                //$all_hour = Carbon::parse($all_seconds)->format('H:i:s');
                $final_data = [
                    'all_units' => $all_unit.' kWh',
                    'all_amount' => $all_amount,
                    'all_charger' => $all_charger,
                    'all_hour' => strtoupper($all_hour),
                    'month_data' => $monthDates
                ];
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $final_data));
            }
            elseif($type == "price")
            {
                if($time_period == "24 hours"){
                    $monthDates = [];
                    for($i = 1; $i <= 23; $i++){
                        $date = Carbon::now('Asia/Kolkata');
                        $start_of_day = Carbon::parse($date->startOfDay()->addHours($i));
                        $end_of_day = Carbon::parse($date->endOfDay());
                        $monthDates[] = [
                            'x' => $i,
                            'start_date' =>  Carbon::parse($start_of_day)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($start_of_day->addMinutes(59)->addSeconds(59))->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "30 days"){
                    $monthDates = [];
                    $date = Carbon::now('Asia/Kolkata');
                    $start_month = $date->startOfMonth()->format('m');
                    $end_of_month = $date->endOfMonth()->format('d');
                    for($i = 1; $i <= 30; $i++){
                        $start_date = Carbon::now('Asia/Kolkata')->startOfDay($i)->subDays($i);
                        $end_date = Carbon::now('Asia/Kolkata')->endOfDay($i)->subDays($i);
                        $monthDates[] = [
                            'x' => $start_date->format("d"),
                            'start_date' =>  Carbon::parse($start_date)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_date)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "1 year"){
                    $monthDates = [];
                    for($i = 1; $i <= 12; $i++){
                        $start_of_month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $end_of_month = $month->endOfMonth($i);
                        $monthDates[] = [
                            'x' => $month->shortMonthName,
                            'start_date' =>  Carbon::parse($start_of_month)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_of_month)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                else{
                    $monthDates = [
                        'code' => "101",
                        'msg' => "Please Enter Valid Time Period"
                    ];
                    return $monthDates;
                }
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Get Price Wise Data SuccessFully";
                $monthDates = $this->analytics_service->getPriceWiseMyChargerAnalytics($user_id,$charger_station_id,$monthDates);
                $collection = collect($monthDates);
                $all_unit = $collection->sum('total_unit');
                $all_amount = $collection->sum('total_amount');
                $all_charger = $collection->sum('total_charger');
                $all_seconds = $collection->sum(function ($date) {
                    $original_date = $date['total_hours'];
                    $parsed  = date_parse($original_date);
                    $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                    return $seconds;
                });
                $all_hour = CarbonInterval::seconds($all_seconds)->cascade()->forHumans(['short' => true]);
                if($all_hour == "1s"){
                    $all_hour = 0;
                }
                $final_data = [
                    'all_units' => $all_unit.' kWh',
                    'all_amount' => $all_amount,
                    'all_charger' => $all_charger,
                    'all_hour' => strtoupper($all_hour),
                    'month_data' => $monthDates
                ];
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $final_data));
            }
            elseif($type == "hourly")
            {
                if($time_period == "24 hours"){
                    $monthDates = [];
                    for($i = 1; $i <= 23; $i++){
                        $date = Carbon::now('Asia/Kolkata');
                        $start_of_day = Carbon::parse($date->startOfDay()->addHours($i));
                        $end_of_day = Carbon::parse($date->endOfDay());
                        $monthDates[] = [
                            'x' => $i,
                            'start_date' =>  Carbon::parse($start_of_day)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($start_of_day->addMinutes(59)->addSeconds(59))->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "30 days"){
                    $monthDates = [];
                    $date = Carbon::now('Asia/Kolkata');
                    $start_month = $date->startOfMonth()->format('m');
                    $end_of_month = $date->endOfMonth()->format('d');
                    for($i = 1; $i <= 30; $i++){
                        $start_date = Carbon::now('Asia/Kolkata')->startOfDay($i)->subDays($i);
                        $end_date = Carbon::now('Asia/Kolkata')->endOfDay($i)->subDays($i);
                        $monthDates[] = [
                            'x' => $start_date->format("d"),
                            'start_date' =>  Carbon::parse($start_date)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_date)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                elseif($time_period == "1 year"){
                    $monthDates = [];
                    for($i = 1; $i <= 12; $i++){
                        $start_of_month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $month = Carbon::now('Asia/Kolkata')->startOfMonth($i)->subMonth($i);
                        $end_of_month = $month->endOfMonth($i);
                        $monthDates[] = [
                            'x' => $month->shortMonthName,
                            'start_date' =>  Carbon::parse($start_of_month)->format('Y-m-d H:i:s'),
                            'end_date' => Carbon::parse($end_of_month)->format('Y-m-d H:i:s'),
                        ];
                    }
                }
                else{
                    $monthDates = [
                        'code' => "101",
                        'msg' => "Please Enter Valid Time Period"
                    ];
                    return $monthDates;
                }
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Get Hour Wise Data SuccessFully";
                $monthDates = $this->analytics_service->getHourWiseMyChargerAnalytics($user_id,$charger_station_id,$monthDates);
                $collection = collect($monthDates);
                $all_unit = $collection->sum('total_unit');
                $all_amount = $collection->sum('total_amount');
                $all_charger = $collection->sum('total_charger');
                $all_seconds = $collection->sum(function ($date) {
                    $original_date = $date['total_hours'];
                    $parsed  = date_parse($original_date);
                    $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
                    return $seconds;
                });
                $all_hour = CarbonInterval::seconds($all_seconds)->cascade()->forHumans(['short' => true]);
                if($all_hour == "1s"){
                    $all_hour = 0;
                }
                $final_data = [
                    'all_units' => $all_unit.' kWh',
                    'all_amount' => $all_amount,
                    'all_charger' => $all_charger,
                    'all_hour' => strtoupper($all_hour),
                    'month_data' => $monthDates
                ];
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $final_data));
            }
            else
            {
                $monthDates = [
                    'code' => "101",
                    'msg' => "Please Enter Valid Type"
                ];
                return $monthDates;
            }
        }else{
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
            return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
        }
    }
}
