<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CentralExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\ChargingHistory;
use App\Http\Models\Charger;
use Maatwebsite\Excel\Facades\Excel;

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
    public function allChargingHistoryExport(Request $request, $startDate = null, $endDate = null)
{
    $query = ChargingHistory::selectRaw("charging_history.id,
                      charging_history.user_id,
                      charging_history.charger_station_id,
                      chargers.name as station_name,
                      CONCAT(users.first_name, ' ', users.last_name) as user_name,
                      charging_history.charging_unit,
                      charging_history.charged_value,
                      charging_history.amount,
                      charging_history.created_at")
        ->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
        ->join('users', 'users.id', '=', 'charging_history.user_id');

    if ($startDate && $endDate) {
        $query->whereBetween('charging_history.created_at', [$startDate, $endDate]);
    }

    $data = $query->orderBy('charging_history.created_at', 'asc')->get();

    foreach ($data as $record) {
        $created_at = new \DateTime($record->created_at, new \DateTimeZone('UTC'));
        $created_at->setTimezone(new \DateTimeZone('Asia/Kolkata'));

        $record->created_date = $created_at->format('Y-m-d');
        $record->created_time = $created_at->format('H:i:s');
        unset($record->created_at);
    }

    $data = $data->map(function ($item) {
        return [
            'id' => $item->id,
            'user_name' => $item->user_name,
            'user_id' => $item->user_id,
            'charger_station_id' => $item->charger_station_id,
            'station_name' => $item->station_name,
            'charging_unit' => $item->charging_unit,
            'amount' => $item->amount,
            'created_date' => $item->created_date,
            'created_time' => $item->created_time,
        ];
    });

    $header = ['ID', 'User Name', 'User ID', 'Charger Station ID', 'Charger Station Name', 'Charging Unit (kWh)', 'Amount', 'Created Date', 'Created Time'];

    if ($startDate && $endDate) {
            $sdate = (new \DateTime($startDate))->format('d-m-Y');
            $edate = (new \DateTime($endDate))->format('d-m-Y');
            $filename = "transactions_{$sdate}_to_{$edate}.csv";
        } else {
            $filename = "transactions_all.csv";
        }

        return Excel::download(new CentralExport($data, $header), $filename);
}

}
