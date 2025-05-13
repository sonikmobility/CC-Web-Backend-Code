<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\ChargingHistory;
use App\Http\Models\Charger;
use DB;

class ChargingHistoryController extends Controller
{
    public function getChargingHistory(Request $request)
    {
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $get_history = ChargingHistory::selectRaw("charging_history.*, chargers.name as station, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
            ->join('users', 'users.id', '=', 'charging_history.user_id')
            ->where(function ($q) use ($request) {
                if (isset($request->charger_station_id) && $request->charger_station_id > 0) {
                    $q->where('charger_station_id', $request->charger_station_id);
                }
            })->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('chargers.name','LIKE', "%{$search}%")->orWhere('charging_history.charging_unit','LIKE', "%{$search}%")->orWhere('users.first_name','LIKE', "%{$search}%")->orWhere('users.last_name','LIKE', "%{$search}%");
                }
            })->orderBy($sort_by, $order_by)->paginate($per_page);
        $code = config('constant.SUCCESS');
        $msg = 'Get Charging History Successfully';
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_history));
    }

    public function getHistoryAllCharger()
    {
        $charger = [['id' => 0, 'name' => 'All']];
        $charger_list = Charger::all();
        $data = [];
        if (!blank($charger_list)) {
            $charger_arr = $charger_list->toArray();
            $data = array_merge($charger, $charger_arr);
        }
        return $data;
    }
}
