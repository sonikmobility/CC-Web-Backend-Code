<?php

namespace App\Http\Services;
use App\Http\Models\ChargingHistory;

class AnalyticsService
{
	public function getUnitWiseMyAnalytics($user_id, $data){
		foreach ($data as $key => $monthDate) {
            $startDate = $monthDate['start_date'];
            $endDate = $monthDate['end_date'];
            $order = ChargingHistory::selectRaw("
                SUM(charging_history.charging_unit) as total_unit,
                SUM(charging_history.total_amount) as total_price,
                COUNT(charging_history.charger_station_id) as total_charger,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                ")
                ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
                ->where("charging_history.user_id",$user_id)
                ->whereRaw("charging_history.created_at >= '{$startDate}' AND charging_history.created_at <= '{$endDate}'")
                ->first();
            $data[$key]['total_unit'] = ($order->total_unit == null) ? 0 : $order->total_unit;
            $data[$key]['total_amount'] = ($order->total_price == null) ? 0 : $order->total_price;
            $data[$key]['total_charger'] = $order->total_charger;
            $data[$key]['total_hours'] = ($order->total_hours == null) ? 0 : $order->total_hours;
            $data[$key]['y']['total_data'] = ($order->total_unit == null) ? 0 : $order->total_unit;
        }
        return $data;
	}

    public function getPriceWiseMyAnalytics($user_id, $data){
        foreach ($data as $key => $monthDate) {
            $startDate = $monthDate['start_date'];
            $endDate = $monthDate['end_date'];
            $order = ChargingHistory::selectRaw("
                SUM(charging_history.charging_unit) as total_unit,
                SUM(charging_history.total_amount) as total_price,
                COUNT(charging_history.charger_station_id) as total_charger,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                ")
                ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
                ->where("charging_history.user_id",$user_id)
                ->whereRaw("charging_history.created_at >= '{$startDate}' AND charging_history.created_at <= '{$endDate}'")
                ->first();
            $data[$key]['total_amount'] = ($order->total_price == null) ? 0 : $order->total_price;
            $data[$key]['total_unit'] = ($order->total_unit == null) ? 0 : $order->total_unit;
            $data[$key]['total_charger'] = $order->total_charger;
            $data[$key]['total_hours'] = ($order->total_hours == null) ? 0 : $order->total_hours;
            $data[$key]['y']['total_data'] = ($order->total_price == null) ? 0 : $order->total_price;
        }
        return $data;
    }

    public function getHourWiseMyAnalytics($user_id, $data){
        foreach ($data as $key => $monthDate) {
            $startDate = $monthDate['start_date'];
            $endDate = $monthDate['end_date'];
            $order = ChargingHistory::selectRaw("
                SUM(charging_history.charging_unit) as total_unit,
                SUM(charging_history.total_amount) as total_price,
                COUNT(charging_history.charger_station_id) as total_charger,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                ")
                ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
                ->where("charging_history.user_id",$user_id)
                ->whereRaw("charging_history.created_at >= '{$startDate}' AND charging_history.created_at <= '{$endDate}'")
                ->first();
            $data[$key]['total_amount'] = ($order->total_price == null) ? 0 : $order->total_price;
            $data[$key]['total_unit'] = ($order->total_unit == null) ? 0 : $order->total_unit;
            $data[$key]['total_charger'] = $order->total_charger;
            $data[$key]['total_hours'] = ($order->total_hours == null) ? 0 : $order->total_hours;
            
            // Convert to Minutes
            if($order->total_hours != null){
                $parsed  = date_parse($order->total_hours);
                $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
            }
            $data[$key]['y']['total_data'] = ($order->total_hours == null) ? 0 : $minute;
        }
        return $data;
    }

    public function getUnitWiseMyChargerAnalytics($user_id, $charger_station_id, $data){
        foreach ($data as $key => $monthDate) {
            $startDate = $monthDate['start_date'];
            $endDate = $monthDate['end_date'];
            $order = ChargingHistory::selectRaw("
                SUM(charging_history.charging_unit) as total_unit,
                SUM(charging_history.total_amount) as total_price,
                COUNT(charging_history.charger_station_id) as total_charger,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                ")
                ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
                ->where("charging_history.user_id",$user_id)
                ->where("charging_history.charger_station_id",$charger_station_id)
                ->whereRaw("charging_history.created_at >= '{$startDate}' AND charging_history.created_at <= '{$endDate}'")
                ->first();
            $data[$key]['total_unit'] = ($order->total_unit == null) ? 0 : $order->total_unit;
            $data[$key]['total_amount'] = ($order->total_price == null) ? 0 : $order->total_price;
            $data[$key]['total_charger'] = $order->total_charger;
            $data[$key]['total_hours'] = ($order->total_hours == null) ? 0 : $order->total_hours;
            $data[$key]['y']['total_data'] = ($order->total_unit == null) ? 0 : $order->total_unit;
        }
        return $data;
    }

    public function getPriceWiseMyChargerAnalytics($user_id, $charger_station_id, $data){
        foreach ($data as $key => $monthDate) {
            $startDate = $monthDate['start_date'];
            $endDate = $monthDate['end_date'];
            $order = ChargingHistory::selectRaw("
                SUM(charging_history.charging_unit) as total_unit,
                SUM(charging_history.total_amount) as total_price,
                COUNT(charging_history.charger_station_id) as total_charger,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                ")
                ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
                ->where("charging_history.user_id",$user_id)
                ->where("charging_history.charger_station_id",$charger_station_id)
                ->whereRaw("charging_history.created_at >= '{$startDate}' AND charging_history.created_at <= '{$endDate}'")
                ->first();
            $data[$key]['total_amount'] = ($order->total_price == null) ? 0 : $order->total_price;
            $data[$key]['total_unit'] = ($order->total_unit == null) ? 0 : $order->total_unit;
            $data[$key]['total_charger'] = $order->total_charger;
            $data[$key]['total_hours'] = ($order->total_hours == null) ? 0 : $order->total_hours;
            $data[$key]['y']['total_data'] = ($order->total_price == null) ? 0 : $order->total_price;
        }
        return $data;
    }

    public function getHourWiseMyChargerAnalytics($user_id, $charger_station_id, $data){
        foreach ($data as $key => $monthDate) {
            $startDate = $monthDate['start_date'];
            $endDate = $monthDate['end_date'];
            $order = ChargingHistory::selectRaw("
                SUM(charging_history.charging_unit) as total_unit,
                SUM(charging_history.total_amount) as total_price,
                COUNT(charging_history.charger_station_id) as total_charger,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                ")
                ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
                ->where("charging_history.user_id",$user_id)
                ->where("charging_history.charger_station_id",$charger_station_id)
                ->whereRaw("charging_history.created_at >= '{$startDate}' AND charging_history.created_at <= '{$endDate}'")
                ->first();
            $data[$key]['total_amount'] = ($order->total_price == null) ? 0 : $order->total_price;
            $data[$key]['total_unit'] = ($order->total_unit == null) ? 0 : $order->total_unit;
            $data[$key]['total_charger'] = $order->total_charger;
            $data[$key]['total_hours'] = ($order->total_hours == null) ? 0 : $order->total_hours;

            // Convert to Minutes
            if($order->total_hours != null){
                $parsed  = date_parse($order->total_hours);
                $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
            }
            $data[$key]['y']['total_data'] = ($order->total_hours == null) ? 0 : $minute;
        }
        return $data;
    }
}