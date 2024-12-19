<?php

namespace App\Http\Controllers;

use App\Http\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Services\CommonService;

class SettingController extends Controller
{
	public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

	public function getSettings(Request $request){
		$code = config('constant.SUCCESS');
        $msg = 'Get Setting Data Successfully';
		$data = Setting::select('name','updated_value')->get();
		return response(array('code' => $code, 'msg' => $msg, 'result' => $data));
	}

	public function getSettingForUser(Request $request){
		$result = [];
		$success = false;
		$get_data = Setting::select('name','updated_value')->get();
		if(!blank($get_data)){
			$success = true;
			$code = config('constant.SUCCESS');
        	$msg = 'Get Setting Data Successfully';
			$result['wallet_data'] = $get_data;
			$result['privacy_policy_url'] = config('constant.APP_URL')."page/privacy-policy/Nw==";
			$result['terms_and_condition_url'] = config('constant.APP_URL')."page/terms-&-conditions/NQ==";
		}
		return response(array('code' => $code, 'msg' => $msg, 'success'=>$success, 'result' => $result));
	}
}