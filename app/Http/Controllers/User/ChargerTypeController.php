<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\ChargerType;
use Illuminate\Support\Facades\File;
use Validator;
use App\Http\Services\CommonService;
use App\Http\Services\ExportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentralExport;

class ChargerTypeController extends Controller
{
	public function getCharingType(Request $request){
		$user_exist = auth('sanctum')->user();
        $get_data = [];
        $success = true;
        if(!blank($user_exist)){
            $get_data = ChargerType::select('id','type')->get();
            $code = config('constant.SUCCESS');
            $msg = "Get Charging Type Successfully";
        }else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result'=> $get_data]);
	}
}