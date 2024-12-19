<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\Charger;
use Carbon\Carbon;
use App\Http\Models\User;
use App\Http\Models\Booking;

class ReportController extends Controller
{
    public function getChargerReport(Request $request)
    {
        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        
        $code = config('constant.SUCCESS');
        $msg = 'Get Charger Report Data Successfully';
        $charger_data = Charger::whereBetween('created_at', [$start_date, $end_date])
            ->when($request->user_id,function($query) use ($request){
                return $query->where('user_id', $request->user_id);
            })
            ->withCount('bookings')
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $charger_data));
    }

    public function getUserReport(Request $request)
    {
        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        
        $code = config('constant.SUCCESS');
        $msg = 'Get User Report Data Successfully';

        $user_data = User::selectRaw("users.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->withCount('bookings')
            ->whereBetween('users.created_at', [$start_date, $end_date])
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);

        return response(array('code' => $code, 'msg' => $msg, 'result' => $user_data));
    }

    public function getMonthwiseBooking(Request $request)
    {
        if ($request->type == "year") {
            $monthDates = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::today()->startOfMonth()->addMonths($i)->format('m');
                $year = Carbon::today()->startOfMonth()->addMonths($i)->format('y');
                $monthDates[] = [
                    'x' => $month . '/' . $year,
                    'start_date' =>  Carbon::today()->addMonths($i)->startOfMonth()->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today()->addMonths($i)->endOfMonth()->format('Y-m-d H:i:s'),
                ];
            }
            $demo = [];
            $total_charging_unit = [];
            $total_charging_time = [];
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $order = Booking::selectRaw("
                        SUM(bookings.is_cancel='0') as confirm_orders,
                        SUM(bookings.is_cancel='1') as cancel_orders,
                        SUM(charging_history.charging_unit) as charging_unit,
                        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                    ")
                    ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
                    ->join('charging_history', 'bookings.id', '=', 'charging_history.booking_id')
                    ->where('bookings.charger_station_id', $request->charger_id)
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->first();
                $monthDates[$key]['y']['confirm_orders'] = $order->confirm_orders;
                $monthDates[$key]['y']['cancel_orders'] = $order->cancel_orders;

                array_push($total_charging_unit,$order->charging_unit??'0');
                // time conver minute
                if($order->total_hours != null){
                    $parsed  = date_parse($order->total_hours);
                    $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
                    array_push($total_charging_time,$minute);
                }
                // time conver minute
            }
            return response()->json([
                'data'=>$monthDates,
                'charging_unit'=>array_sum($total_charging_unit),
                'charging_time'=>round(array_sum($total_charging_time),2)
            ]);
        } elseif ($request->type == "month") {
            $first_day = Carbon::now()->firstOfMonth()->day;
            $month_day = Carbon::now('Asia/Kolkata')->day;
            $current_month = Carbon::now('Asia/Kolkata')->month;
            $monthDates = [];
            for ($i = $month_day; $i >= 1; $i--) {
                $day = Carbon::today('Asia/Kolkata')->subDay($i - 1)->startOfDay()->format('d');
                $month = Carbon::today('Asia/Kolkata')->startOfMonth($i)->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::today('Asia/Kolkata')->startOfDay()->subDay($i - 1)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today('Asia/Kolkata')->subDay($i - 1)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            $total_charging_unit = [];
            $total_charging_time = [];
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $order = Booking::selectRaw("
                        SUM(bookings.is_cancel='0') as confirm_orders,
                        SUM(bookings.is_cancel='1') as cancel_orders,
                        SUM(charging_history.charging_unit) as charging_unit,
                        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                    ")
                    ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
                    ->join('charging_history', 'bookings.id', '=', 'charging_history.booking_id')
                    ->where('bookings.charger_station_id', $request->charger_id)
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->first();
                $monthDates[$key]['y']['confirm_orders'] = $order->confirm_orders;
                $monthDates[$key]['y']['cancel_orders'] = $order->cancel_orders;
                // time conver minute
                if($order->total_hours != null){
                    $parsed  = date_parse($order->total_hours);
                    $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
                    array_push($total_charging_time,$minute);
                }
                // time conver minute
                array_push($total_charging_unit,$order->charging_unit??'0');
            }
            return response()->json([
                'data'=>$monthDates,
                'charging_unit'=>array_sum($total_charging_unit),
                'charging_time'=>round(array_sum($total_charging_time),2)
            ]);
        } else {
            $monthDates = [];
            for ($i = 0; $i < 7; $i++) {
                $day = Carbon::now()->startOfWeek()->addDay($i)->format('d');
                $month = Carbon::now('Asia/Kolkata')->endOfWeek()->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::now()->startOfWeek()->addDay($i)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::now()->startOfWeek()->addDay($i)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            $total_charging_unit = [];
            $total_charging_time = [];
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $order = Booking::selectRaw("
                        SUM(bookings.is_cancel='0') as confirm_orders,
                        SUM(bookings.is_cancel='1') as cancel_orders,
                        SUM(charging_history.charging_unit) as charging_unit,
                        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                    ")
                    ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
                    ->join('charging_history', 'bookings.id', '=', 'charging_history.booking_id')
                    ->where('bookings.charger_station_id', $request->charger_id)
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->first();
                $monthDates[$key]['y']['confirm_orders'] = $order->confirm_orders;
                $monthDates[$key]['y']['cancel_orders'] = $order->cancel_orders;
                array_push($total_charging_unit,$order->charging_unit??'0');
                // time conver minute
                if($order->total_hours != null){
                    $parsed  = date_parse($order->total_hours);
                    $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
                    array_push($total_charging_time,$minute);
                }
                // time conver minute
            }
            return response()->json([
                'data'=>$monthDates,
                'charging_unit'=>array_sum($total_charging_unit),
                'charging_time'=>round(array_sum($total_charging_time),2)
            ]);
        }
    }

    public function getMonthwiseUserBooking(Request $request)
    {
        if ($request->type == "year") {
            $monthDates = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::today()->startOfMonth()->addMonths($i)->format('m');
                $year = Carbon::today()->startOfMonth()->addMonths($i)->format('y');
                $monthDates[] = [
                    'x' => $month . '/' . $year,
                    'start_date' =>  Carbon::today()->addMonths($i)->startOfMonth()->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today()->addMonths($i)->endOfMonth()->format('Y-m-d H:i:s'),
                ];
            }
            $total_charging_unit = [];
            $total_charging_time = [];
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $order = Booking::selectRaw("
                        SUM(bookings.is_cancel='0') as confirm_orders,
                        SUM(bookings.is_cancel='1') as cancel_orders,
                        SUM(charging_history.charging_unit) as charging_unit,
                        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                    ")
                    ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
                    ->join('charging_history', 'bookings.id', '=', 'charging_history.booking_id')
                    ->where('bookings.user_id', $request->user_id)
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->first();
                $monthDates[$key]['y']['confirm_orders'] = $order->confirm_orders;
                $monthDates[$key]['y']['cancel_orders'] = $order->cancel_orders;

                array_push($total_charging_unit,$order->charging_unit??'0');
                // time conver minute
                if($order->total_hours != null){
                    $parsed  = date_parse($order->total_hours);
                    $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
                    array_push($total_charging_time,$minute);
                }
                // time conver minute
            }
            return response()->json([
                'data'=>$monthDates,
                'charging_unit'=>array_sum($total_charging_unit),
                'charging_time'=>round(array_sum($total_charging_time),2)
            ]);
            // return array_reverse($monthDates);
        } elseif ($request->type == "month") {
            $first_day = Carbon::now()->firstOfMonth()->day;
            $month_day = Carbon::now('Asia/Kolkata')->day;
            $current_month = Carbon::now('Asia/Kolkata')->month;
            $monthDates = [];
            for ($i = $month_day; $i >= 1; $i--) {
                $day = Carbon::today('Asia/Kolkata')->subDay($i - 1)->startOfDay()->format('d');
                $month = Carbon::today('Asia/Kolkata')->startOfMonth($i)->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::today('Asia/Kolkata')->startOfDay()->subDay($i - 1)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today('Asia/Kolkata')->subDay($i - 1)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            $total_charging_unit = [];
            $total_charging_time = [];
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $order = Booking::selectRaw("
                        SUM(bookings.is_cancel='0') as confirm_orders,
                        SUM(bookings.is_cancel='1') as cancel_orders,
                        SUM(charging_history.charging_unit) as charging_unit,
                        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                    ")
                    ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
                    ->join('charging_history', 'bookings.id', '=', 'charging_history.booking_id')
                    ->where('bookings.user_id', $request->user_id)
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->first();
                $monthDates[$key]['y']['confirm_orders'] = $order->confirm_orders;
                $monthDates[$key]['y']['cancel_orders'] = $order->cancel_orders;

                array_push($total_charging_unit,$order->charging_unit??'0');
                // time conver minute
                if($order->total_hours != null){
                    $parsed  = date_parse($order->total_hours);
                    $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
                    array_push($total_charging_time,$minute);
                }
                // time conver minute
            }
            // return array_reverse($monthDates);

            return response()->json([
                'data'=>$monthDates,
                'charging_unit'=>array_sum($total_charging_unit),
                'charging_time'=>round(array_sum($total_charging_time),2)
            ]);

        } else {
            $monthDates = [];
            for ($i = 0; $i < 7; $i++) {
                $day = Carbon::now()->startOfWeek()->addDay($i)->format('d');
                $month = Carbon::now('Asia/Kolkata')->endOfWeek()->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::now()->startOfWeek()->addDay($i)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::now()->startOfWeek()->addDay($i)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            $total_charging_unit = [];
            $total_charging_time = [];
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $order = Booking::selectRaw("
                        SUM(bookings.is_cancel='0') as confirm_orders,
                        SUM(bookings.is_cancel='1') as cancel_orders,
                        SUM(charging_history.charging_unit) as charging_unit,
                        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(charging_history.charging_end_time,charging_history.charging_start_time)))) as total_hours
                    ")
                    ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
                    ->join('charging_history', 'bookings.id', '=', 'charging_history.booking_id')
                    ->where('bookings.user_id', $request->user_id)
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->first();
                $monthDates[$key]['y']['confirm_orders'] = $order->confirm_orders;
                $monthDates[$key]['y']['cancel_orders'] = $order->cancel_orders;

                array_push($total_charging_unit,$order->charging_unit??'0');
                // time conver minute
                if($order->total_hours != null){
                    $parsed  = date_parse($order->total_hours);
                    $minute = ($parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'])/60;
                    array_push($total_charging_time,$minute);
                }
                // time conver minute
            }

            return response()->json([
                'data'=>$monthDates,
                'charging_unit'=>array_sum($total_charging_unit),
                'charging_time'=>round(array_sum($total_charging_time),2) 
            ]);
            // return $monthDates;
        }
    }

    public function getMonthwiseEarning(Request $request)
    {
        if ($request->type == "year") {
            $monthDates = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::today()->startOfMonth()->addMonths($i)->format('m');
                $year = Carbon::today()->startOfMonth()->addMonths($i)->format('y');
                $monthDates[] = [
                    'x' => $month . '/' . $year,
                    'start_date' =>  Carbon::today()->addMonths($i)->startOfMonth()->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today()->addMonths($i)->endOfMonth()->format('Y-m-d H:i:s'),
                ];
            }
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $sales = Booking::selectRaw("
                            SUM(bookings.pre_auth_charge) as pre_auth_charge,
                            SUM(bookings.final_charge) as final_charge
                        ")
                    ->whereNotNull('bookings.pre_auth_transaction_id')
                    ->whereNotNull('bookings.final_transaction_id')
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->where('bookings.payment_status', 'Completed')
                    ->where('charger_station_id', $request->charger_id)
                    ->first();
                if (!empty($sales)) {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", $sales->pre_auth_charge);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", $sales->final_charge);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", ($sales->pre_auth_charge + $sales->final_charge));
                } else {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", 0);
                }
            }
            return array_reverse($monthDates);
        } else if ($request->type == "month") {
            $first_day = Carbon::now()->firstOfMonth()->day;
            $month_day = Carbon::now('Asia/Kolkata')->day;
            $current_month = Carbon::now('Asia/Kolkata')->month;
            $monthDates = [];
            for ($i = $month_day; $i >= 1; $i--) {
                $day = Carbon::today('Asia/Kolkata')->subDay($i - 1)->startOfDay()->format('d');
                $month = Carbon::today('Asia/Kolkata')->startOfMonth($i)->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::today('Asia/Kolkata')->startOfDay()->subDay($i - 1)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today('Asia/Kolkata')->subDay($i - 1)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $sales = Booking::selectRaw("
                            SUM(bookings.pre_auth_charge) as pre_auth_charge,
                            SUM(bookings.final_charge) as final_charge
                        ")
                    ->whereNotNull('bookings.pre_auth_transaction_id')
                    ->whereNotNull('bookings.final_transaction_id')
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->where('bookings.payment_status', 'Completed')
                    ->where('charger_station_id', $request->charger_id)
                    ->first();
                if (!empty($sales)) {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", $sales->pre_auth_charge);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", $sales->final_charge);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", ($sales->pre_auth_charge + $sales->final_charge));
                } else {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", 0);
                }
            }
            return $monthDates;
        } else {
            for ($i = 0; $i < 7; $i++) {
                $day = Carbon::now()->startOfWeek()->addDay($i)->format('d');
                $month = Carbon::now('Asia/Kolkata')->endOfWeek()->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::now()->startOfWeek()->addDay($i)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::now()->startOfWeek()->addDay($i)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $sales = Booking::selectRaw("
                            SUM(bookings.pre_auth_charge) as pre_auth_charge,
                            SUM(bookings.final_charge) as final_charge
                        ")
                    ->whereNotNull('bookings.pre_auth_transaction_id')
                    ->whereNotNull('bookings.final_transaction_id')
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->where('bookings.payment_status', 'Completed')
                    ->where('charger_station_id', $request->charger_id)
                    ->first();
                if (!empty($sales)) {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", $sales->pre_auth_charge);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", $sales->final_charge);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", ($sales->pre_auth_charge + $sales->final_charge));
                } else {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", 0);
                }
            }
            return $monthDates;
        }
    }

    public function getMonthwiseUserEarning(Request $request)
    {
        if ($request->type == "year") {
            $monthDates = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::today()->startOfMonth()->addMonths($i)->format('m');
                $year = Carbon::today()->startOfMonth()->addMonths($i)->format('y');
                $monthDates[] = [
                    'x' => $month . '/' . $year,
                    'start_date' =>  Carbon::today()->addMonths($i)->startOfMonth()->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today()->addMonths($i)->endOfMonth()->format('Y-m-d H:i:s'),
                ];
            }
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $sales = Booking::selectRaw("
                            SUM(bookings.pre_auth_charge) as pre_auth_charge,
                            SUM(bookings.final_charge) as final_charge
                        ")
                    ->whereNotNull('bookings.pre_auth_transaction_id')
                    ->whereNotNull('bookings.final_transaction_id')
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->where('bookings.payment_status', 'Completed')
                    ->where('bookings.user_id', $request->user_id)
                    ->first();
                if (!empty($sales)) {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", $sales->pre_auth_charge);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", $sales->final_charge);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", ($sales->pre_auth_charge + $sales->final_charge));
                } else {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", 0);
                }
            }
            return array_reverse($monthDates);
        } else if ($request->type == "month") {
            $first_day = Carbon::now()->firstOfMonth()->day;
            $month_day = Carbon::now('Asia/Kolkata')->day;
            $current_month = Carbon::now('Asia/Kolkata')->month;
            $monthDates = [];
            for ($i = $month_day; $i >= 1; $i--) {
                $day = Carbon::today('Asia/Kolkata')->subDay($i - 1)->startOfDay()->format('d');
                $month = Carbon::today('Asia/Kolkata')->startOfMonth($i)->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::today('Asia/Kolkata')->startOfDay()->subDay($i - 1)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::today('Asia/Kolkata')->subDay($i - 1)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $sales = Booking::selectRaw("
                            SUM(bookings.pre_auth_charge) as pre_auth_charge,
                            SUM(bookings.final_charge) as final_charge
                        ")
                    ->whereNotNull('bookings.pre_auth_transaction_id')
                    ->whereNotNull('bookings.final_transaction_id')
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->where('bookings.payment_status', 'Completed')
                    ->where('bookings.user_id', $request->user_id)
                    ->first();
                if (!empty($sales)) {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", $sales->pre_auth_charge);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", $sales->final_charge);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", ($sales->pre_auth_charge + $sales->final_charge));
                } else {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", 0);
                }
            }
            return $monthDates;
        } else {
            for ($i = 0; $i < 7; $i++) {
                $day = Carbon::now()->startOfWeek()->addDay($i)->format('d');
                $month = Carbon::now('Asia/Kolkata')->endOfWeek()->format('m');
                $monthDates[] = [
                    'x' => $day . '/' . $month,
                    'start_date' =>  Carbon::now()->startOfWeek()->addDay($i)->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::now()->startOfWeek()->addDay($i)->endOfDay()->format('Y-m-d H:i:s'),
                ];
            }
            foreach ($monthDates as $key => $monthDate) {
                $startDate = $monthDate['start_date'];
                $endDate = $monthDate['end_date'];
                $sales = Booking::selectRaw("
                            SUM(bookings.pre_auth_charge) as pre_auth_charge,
                            SUM(bookings.final_charge) as final_charge
                        ")
                    ->whereNotNull('bookings.pre_auth_transaction_id')
                    ->whereNotNull('bookings.final_transaction_id')
                    ->whereRaw("bookings.created_at >= '{$startDate}' AND bookings.created_at <= '{$endDate}'")
                    ->where('bookings.payment_status', 'Completed')
                    ->where('bookings.user_id', $request->user_id)
                    ->first();
                if (!empty($sales)) {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", $sales->pre_auth_charge);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", $sales->final_charge);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", ($sales->pre_auth_charge + $sales->final_charge));
                } else {
                    $monthDates[$key]['y']['pre_auth_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['final_charge'] = sprintf("%0.2f", 0);
                    $monthDates[$key]['y']['total_charge'] = sprintf("%0.2f", 0);
                }
            }
            return $monthDates;
        }
    }
}
