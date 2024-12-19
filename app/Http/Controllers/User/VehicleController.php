<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\VehicleMake;
use App\Http\Models\VehicleModel;
use App\Http\Models\User;
use App\Http\Models\UserVehicle;
use Validator;
use App\Http\Resources\MyVehicleResource;

class VehicleController extends Controller
{
    public function getMakeVehicle()
    {
        $data = VehicleMake::select('id', 'name')->get();
        $code = config('constant.SUCCESS');
        $msg = "Get Make Vehicle Data";
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $data]);
    }

    public function addMakeVehicle(Request $request)
    {
        $user_exist = auth('sanctum')->user();
        $success = false;
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'vehicle_name' => 'required|unique:vehicle_make,name',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $data = [
                    'name' => $request->vehicle_name
                ];
                $vehicle_data = VehicleMake::create($data);
                if ($vehicle_data) {
                    $code = config('constant.SUCCESS');
                    $msg = "Make Vehicle Created Successfully";
                    $success = true;
                } else {
                    $code = config('constant.UNSUCCESS');
                    $msg = "Something went wrong";
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg]);
    }

    public function getModelVehicle(Request $request)
    {
        $success = false;
        $validator = Validator::make($request->all(), [
            'make_id' => 'required'
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
        } else {
            $model_data = VehicleModel::select('id', 'name')->where('vehicle_make_id', $request->make_id)->get();
            if (!blank($model_data)) {
                $code = config('constant.SUCCESS');
                $msg = "Get Model Vehicle Data";
                $result = $model_data;
                $success = true;
            } else {
                $code = config('constant.UNSUCCESS');
                $msg = "No Model Data Found";
            }
            return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result]);
        }
    }

    public function getAllModelVehicle(Request $request){
        $model_data = VehicleModel::select('id', 'name')->get();
        $result = [];
        $success = false;
        if (!blank($model_data)) {
            $code = config('constant.SUCCESS');
            $msg = "Get Model Vehicle Data";
            $result = $model_data;
            $success = true;
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "No Model Data Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result]);
    }

    public function addModelVehicle(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'model_name' => 'required|unique:vehicle_model,name',
                'make_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $check_model = VehicleModel::where('vehicle_make_id', $request->make_id)->where('name', $request->model_name)->first();
                if ($check_model) {
                    $code = config('constant.UNSUCCESS');
                    $msg = "Model Vehicle Already exist";
                } else {
                    $data = [
                        'name' => $request->model_name,
                        'vehicle_make_id' => $request->make_id
                    ];
                    $vehicle_data = VehicleModel::create($data);
                    if ($vehicle_data) {
                        $code = config('constant.SUCCESS');
                        $msg = "Model Vehicle Created Successfully";
                        $success = true;
                    } else {
                        $code = config('constant.UNSUCCESS');
                        $msg = "Something went wrong";
                    }
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg]);
    }

    public function AddUserVehicle(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        $update_flag = false;
        $create_flag = false;
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'model_id' => 'required',
                'make_id' => 'required',
                'battery_size' => 'required',
                'charger_type' => 'required',
                'mark_as_primary' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                if (!blank($request->id)) {
                    $update_flag = true;
                } else {
                    $create_flag = true;
                }
                if ($create_flag) {
                    $check_vehicle = UserVehicle::where('vehicle_make_id', $request->make_id)->where('vehicle_model_id', $request->model_id)->where(function ($q) use ($request) {
                        if (!blank($request->registration_number) || $request->registration_number > 0) {
                            $q->where('registration_number', $request->registration_number);
                        }
                    })->where('user_id', $user_id)->first();
                } else {
                    $check_vehicle = UserVehicle::where('id', "!=", $request->id)->where('vehicle_make_id', $request->make_id)->where('vehicle_model_id', $request->model_id)->where(function ($q) use ($request) {
                        if (!blank($request->registration_number) || $request->registration_number > 0) {
                            $q->where('registration_number', $request->registration_number);
                        }
                    })->where('user_id', $user_id)->first();
                }

                if($request->mark_as_primary){
                    if($request->mark_as_primary == '1'){
                        $get_all_vehicle = UserVehicle::where('user_id', $user_id)->get();
                        if(!blank($get_all_vehicle)){
                            foreach($get_all_vehicle as $data){
                                $data->update(['mark_as_primary' => 0]);
                            }
                        }
                    }else{
                        $check_mark_as_primary = UserVehicle::where('user_id', $user_id)->first();
                        if(!blank($check_mark_as_primary)){
                            $check_mark_as_primary->update(['mark_as_primary' => 1]);
                        }
                    }
                }else{
                    $check_mark_as_primary = UserVehicle::where('user_id', $user_id)->where('mark_as_primary','=',1)->first();
                    if(blank($check_mark_as_primary)){
                        $request->mark_as_primary = 1;
                    }else{
                        $request->mark_as_primary = 0;
                    }
                }

                $data = [
                    'vehicle_make_id' => $request->make_id,
                    'vehicle_model_id' => $request->model_id,
                    'user_id' => $user_id,
                    'registration_number' => $request->registration_number ? $request->registration_number : null,
                    'battery_size' => $request->battery_size ? $request->battery_size : null,
                    'charger_type' => $request->charger_type ? $request->charger_type : null,
                    'mark_as_primary' => $request->mark_as_primary,
                ];

                $user_data = [
                    'is_skip' => 1
                ];
                if (!blank($check_vehicle)) {
                    $code = config('constant.UNSUCCESS');
                    $msg = "Data Already Exist To This User";
                } else {
                    $success = true;
                    if (!blank($request->id)) {
                        $vehicle_data = UserVehicle::find($request->id);
                        $update_vehicle_data = [
                            'vehicle_make_id' => $request->make_id ? $request->make_id : $vehicle_data->vehicle_make_id,
                            'vehicle_model_id' => $request->model_id ? $request->model_id : $vehicle_data->vehicle_model_id,
                            'user_id' => $user_id ? $user_id : $vehicle_data->user_id,
                            'registration_number' => $request->registration_number ? $request->registration_number : $vehicle_data->registration_number,
                            'battery_size' => $request->battery_size ? $request->battery_size : $vehicle_data->battery_size,
                            'charger_type' => $request->charger_type ? $request->charger_type : $vehicle_data->charger_type,
                            'mark_as_primary' => $request->mark_as_primary ? $request->mark_as_primary : $vehicle_data->mark_as_primary
                        ];
                        $update_vehicle = $vehicle_data->update($update_vehicle_data);
                        $id = $vehicle_data->id;
                        $code = config('constant.SUCCESS');
                        $msg = "User Vehicle Updated Successfully";
                    } else {
                        $create_vehicle = UserVehicle::create($data);
                        $id = $create_vehicle->id;
                        $user_update_data = User::find($user_id);
                        $update_user_data = $user_update_data->update($user_data);
                        $code = config('constant.SUCCESS');
                        $msg = "User Vehicle Created Successfully";
                    }
                    $get_data = UserVehicle::selectRaw('user_vehicle.*, vehicle_make.name as vehicle_make_name, vehicle_model.name as vehicle_model_name')
                        ->join('vehicle_make', 'vehicle_make.id', '=', 'user_vehicle.vehicle_make_id')
                        ->join('vehicle_model', 'vehicle_model.id', '=', 'user_vehicle.vehicle_model_id')
                        ->where(function ($q) use ($id) {
                            if (isset($id) && $id > 0) {
                                $q->where('user_vehicle.id', $id);
                            }
                        })
                        ->where('user_id', $user_id)->get();
                    $result = $get_data;
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result]);
    }

    public function isSkipped(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'is_skip' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $check_skip = User::where('id', $user_id)->where('is_skip', $request->is_skip)->first();
                if (!blank($check_skip)) {
                    $code = config('constant.UNSUCCESS');
                    $msg = "User Already Assign This Value";
                } else {
                    $data = [
                        'is_skip' => $request->is_skip,
                    ];
                    $user = User::find($user_id);
                    $update_user = $user->update($data);
                    $code = config('constant.SUCCESS');
                    $msg = "Skipped Successfully";
                    $success = true;
                    $result = $user;
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result]);
    }

    public function myVehicle(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $result = UserVehicle::selectRaw('user_vehicle.*, vehicle_make.name as vehicle_make_name, vehicle_model.name as vehicle_model_name, charger_type.type as vehicle_charger_type_name')
                ->join('vehicle_make', 'vehicle_make.id', '=', 'user_vehicle.vehicle_make_id')
                ->join('vehicle_model', 'vehicle_model.id', '=', 'user_vehicle.vehicle_model_id')
                ->join('charger_type', 'charger_type.id', '=', 'user_vehicle.charger_type')
                ->where(function ($q) use ($request) {
                    if (isset($request->id) && $request->id > 0) {
                        $q->where('user_vehicle.vehicle_make_id', $request->id);
                    }
                    if (isset($request->model_id) && $request->model_id > 0) {
                        $q->where('user_vehicle.vehicle_model_id', $request->model_id);
                    }
                    if (isset($request->registration_number) && !blank($request->registration_number)) {
                        $q->where('user_vehicle.registration_number', $request->registration_number);
                    }
                })
                ->where('user_id', $user_id)->get();
            $code = config('constant.SUCCESS');
            $success = true;
            $msg = "My Vehicle List";
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json([
            'code' => $code, 'success' => $success, 'msg' => $msg, 'result' =>
            $result
        ]);
        // return response()->json([
        //     'code' => $code, 'success' => $success, 'msg' => $msg, 'result' =>
        //     MyVehicleResource::collection($result->toArray())
        // ]);

    }

    // public function filterMyVehicle(Request $request)
    // {
    //     $success = false;
    //     $user_exist = auth('sanctum')->user();
    //     $data = [];
    //     if (!blank($user_exist)) {
    //         $user_id = auth('sanctum')->user()->id;
    //         $data = UserVehicle::selectRaw('user_vehicle.*, vehicle_make.name as make_name, vehicle_model.name as model_name')
    //             ->join('vehicle_make', 'vehicle_make.id', '=', 'user_vehicle.vehicle_make_id')
    //             ->join('vehicle_model', 'vehicle_model.id', '=', 'user_vehicle.vehicle_model_id')
    //             ->where(function ($q) use ($request) {
    //                 if (isset($request->make_id) && $request->make_id > 0) {
    //                     $q->where('user_vehicle.vehicle_make_id', $request->make_id);
    //                 }
    //                 if (isset($request->model_id) && $request->model_id > 0) {
    //                     $q->where('user_vehicle.vehicle_model_id', $request->model_id);
    //                 }
    //                 if (isset($request->registration_number) && !blank($request->registration_number)) {
    //                     $q->where('user_vehicle.registration_number', $request->registration_number);
    //                 }
    //             })
    //             ->where('user_id', $user_id)->get();
    //         $code = config('constant.SUCCESS');
    //         $success = true;
    //         $msg = "Filter User Vehicle List";
    //     } else {
    //         $code = config('constant.UNSUCCESS');
    //         $msg = "User Not Found";
    //     }
    //     return response()->json([
    //         'code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $data
    //     ]);
    // }

    public function filterMyVehicleList(Request $request)
    {
        $response = [];
        $success = false;
        $user_exist = auth('sanctum')->user();
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $data = UserVehicle::selectRaw('vehicle_make.id as id, vehicle_make.name as name')
                ->join('vehicle_make', 'vehicle_make.id', '=', 'user_vehicle.vehicle_make_id')
                ->where('user_id', $user_id)
                ->groupBy('vehicle_make.id')
                ->get();
            if (!blank($data)) {
                $fData = (object) array();
                $fData->id = 0;
                $fData->name = 'All';
                $response = array_merge([$fData], $data->toArray());
            }
            $code = config('constant.SUCCESS');
            $success = true;
            $msg = "Filter User Vehicle Make List";
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json([
            'code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $response
        ]);
    }
}
