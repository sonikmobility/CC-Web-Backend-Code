<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\VehicleMake;
use App\Http\Models\VehicleModel;
use App\Http\Models\UserVehicle;
use App\Http\Models\User;
use Validator;
use App\Http\Resources\MakeModelResource;

class MakeModelController extends Controller
{
    public function addVehicle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_make_id' => 'required|integer',
            'vehicle_model_id' => 'required|integer',
            'user_id' => 'required',
            'battery_size' => 'required',
            'charger_type' => 'required'
        ]);
        $data = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $check_vehicle_data = UserVehicle::where('vehicle_make_id', $request->vehicle_make_id)->where('vehicle_model_id', $request->vehicle_model_id)->where('user_id', $request->user_id)->first();
                if (!blank($check_vehicle_data)) {
                    $code = config('constant.UNSUCCESS');
                    $msg = 'Vehicle Already Exist';
                } else {
                    $code = config('constant.SUCCESS');
                    $msg = 'Vehicle Created Successfully';

                    $vehicle_data = [
                        'vehicle_make_id' => $request->vehicle_make_id,
                        'vehicle_model_id' => $request->vehicle_model_id,
                        'user_id' => $request->user_id,
                        'battery_size' => $request->battery_size,
                        'charger_type' => $request->charger_type,
                    ];
                    $data = UserVehicle::updateOrCreate(['vehicle_model_id' => $request->vehicle_model_id, 'user_id' => $request->user_id], $vehicle_data);
                }
                return response(array('code' => $code, 'msg' => $msg, 'result' => $data));
            } catch (Exception $e) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $e->getMessage()]);
            }
        }
    }

    public function showUserVehicle(Request $request)
    {
        try {
            $vehicle_data = User::find($request->user_id);
            if (!blank($vehicle_data)) {
                $code = config('constant.SUCCESS');
                $msg = 'Success';
                $data = new MakeModelResource($vehicle_data);
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = 'No Data Found';
                $data = [];
            }

            return response(array('code' => $code, 'msg' => $msg, 'result' =>  $data));
        } catch (Exception $e) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $e->getMessage()]);
        }
    }

    public function getModelMakeData(Request $request)
    {
        if (!blank($request->make_id)) {
            $get_data = VehicleMake::where('id', $request->make_id)->select('id', 'name')->with('vehicleModels')->get();
        } else {
            $get_data = VehicleMake::select('id', 'name')->with('vehicleModels')->get();
        }

        if (!blank($get_data)) {
            $code = config('constant.SUCCESS');
            $msg = 'Success';
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = 'No Data Found';
        }
        return response(array('code' => $code, 'msg' => $msg, 'result' =>  $get_data));
    }
}
