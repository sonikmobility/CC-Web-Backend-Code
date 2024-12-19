<?php

namespace App\Http\Controllers\User;

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
	public function getBatterySize(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_model_id' => 'required',
            'charger_type_id' => 'required',
        ]);
        $code = config('constant.UNSUCCESS');
        $msg = "Not Found";
		$user_exist = auth('sanctum')->user();
        $get_data = [];
        $success = true;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            if(!blank($user_exist)){
                $get_data = BatterySize::where('vehicle_model_id',$request->vehicle_model_id)->where('charger_type_id',$request->charger_type_id)->select('id','name')->get();
                $code = config('constant.SUCCESS');
                $msg = "Get Battery Size Successfully";
            }else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result'=> $get_data]);
	}
}