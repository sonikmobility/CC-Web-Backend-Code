<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\BatterySize;
use Illuminate\Support\Facades\File;
use Validator;
use App\Http\Services\CommonService;
use App\Http\Services\ExportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentralExport;

class BatterySizeController extends Controller
{
	public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

	public function getAllBatterySize(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get All Battery Size List";
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $result = BatterySize::selectRaw("battery_size.*,vehicle_model.name as vehicle_model_name,charger_type.type as type")
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('battery_size.name','LIKE', "%{$search}%")->orWhere('vehicle_model.name','LIKE', "%{$search}%")->orWhere('charger_type.type','LIKE', "%{$search}%");
                }
            })
            ->join('vehicle_model', 'vehicle_model.id', '=', 'battery_size.vehicle_model_id')
            ->join('charger_type', 'charger_type.id', '=', 'battery_size.charger_type_id')
            ->orderBy($sort_by, $order_by)->paginate($per_page);
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function showBatterySize(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Show Battery Size Data";
        $result = BatterySize::with('vehicleModel','chargerType')->where('id', $request->id)->first();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function addBatterySize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'vehicle_model_id' => 'required',
            'charger_type_id' => 'required',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $check_data = BatterySize::where('vehicle_model_id',$request->vehicle_model_id)->where('charger_type_id',$request->charger_type_id)->first();
            if(!blank($check_data)){
                $code = config('constant.UNSUCCESS');
                $msg = "Charger type and Vehicle model data already exist";
            }else{
                $code = config('constant.SUCCESS');
                $msg = "Battery Size Data Created Successfully";
                $data = [
                    'name' => $request->name,
                    'vehicle_model_id' => $request->vehicle_model_id,
                    'charger_type_id' => $request->charger_type_id,
                ];
                $result = BatterySize::create($data);
            }
            
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function updateBatterySize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'vehicle_model_id' => 'required',
            'charger_type_id' => 'required',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Battery Size Data Updated Successfully";
            $check_size = BatterySize::where('id', '!=', $request->id)->where('vehicle_model_id',$request->vehicle_model_id)->where('charger_type_id',$request->charger_type_id)->first();
            if (!blank($check_size)) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Data Already Exist", 'result' => $result]);
            }
            $battery_size = BatterySize::find($request->id);
            $data = [
                'name' => $request->name,
                'vehicle_model_id' => $request->vehicle_model_id,
                'charger_type_id' => $request->charger_type_id,
            ];
            $result = $battery_size->update($data);
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function deleteBatterySize(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Delete Battery Size Data Successfully";
        $result = BatterySize::find($request->id)->delete();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function batterySizeExport()
    {
        $export_data = $this->exportService->batterySizeExport();
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Battery-size.csv');
    }
}