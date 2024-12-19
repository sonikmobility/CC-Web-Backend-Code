<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\UserVehicle;
use App\Http\Models\VehicleMake;
use App\Http\Models\ChargerType;
use App\Http\Models\BatterySize;
use App\Http\Models\VehicleModel;
use Validator;
use App\Http\Services\ExportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentralExport;

class VehicleController extends Controller
{
    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function getUserVehicle(Request $request)
    {
        $user_id = $request->user_id;
        $result = [];
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        if (!blank($user_id)) {
            $result = UserVehicle::selectRaw("user_vehicle.*, vehicle_make.name as vehicle_make_name, vehicle_model.name as vehicle_model_name, charger_type.type as type, user_vehicle.battery_size as size")
            ->join('vehicle_make', 'vehicle_make.id', '=', 'user_vehicle.vehicle_make_id')
            ->join('vehicle_model', 'vehicle_model.id', '=', 'user_vehicle.vehicle_model_id')
            ->join('charger_type', 'charger_type.id', '=', 'user_vehicle.charger_type')
            ->where('user_id', $user_id)
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
            $code = config('constant.SUCCESS');
            $msg = "My Vehicle List";
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "Something went wrong";
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function getAllVehicleMake(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get All Make Vehicle List";
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $result = VehicleMake::where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%");
                }
            })->orderBy($sort_by, $order_by)->paginate($per_page);
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function getAllVehicleModel(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get All Model Vehicle List";
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $result = VehicleModel::selectRaw("vehicle_model.*, vehicle_make.name as vehicle_make_name")
            ->join('vehicle_make', 'vehicle_make.id', '=', 'vehicle_model.vehicle_make_id')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('vehicle_model.name','LIKE', "%{$search}%")->orWhere('vehicle_make.name','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)->paginate($per_page);
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function showVehicleMake(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Show Vehicle Make Data";
        $result = VehicleMake::select('id', 'name')->where('id', $request->id)->first();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function showVehicleModel(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Show Vehicle Model Data";
        $result = VehicleModel::with('userVehicleMakes')->where('id', $request->id)->first();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function addVehicleMake(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:vehicle_make,name',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Vehicle Make Data Created Successfully";
            $data = [
                'name' => $request->name
            ];
            $result = VehicleMake::create($data);
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function updateVehicleMake(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Vehicle Make Data Updated Successfully";
            $check_make = VehicleMake::where('id', '!=', $request->id)->where('name', $request->name)->first();
            if (!blank($check_make)) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Make Name Already Exist", 'result' => $result]);
            }
            $vehicle_make = VehicleMake::find($request->id);
            $data = [
                'name' => $request->name
            ];
            $result = $vehicle_make->update($data);
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function deleteVehicleMake(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Delete Vehicle Make Data Successfully";
        $result = VehicleMake::find($request->id)->delete();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function deleteVehicleModel(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Delete Vehicle Model Data Successfully";
        $result = VehicleModel::find($request->id)->delete();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function getMake(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get Vehicle Make Data Successfully";
        $result = VehicleMake::select('id', 'name')->get()->toArray();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function getTypes(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get Charger Type Data Successfully";
        $result = ChargerType::select('id', 'type')->get()->toArray();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function getSizes(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get Battery Size Data Successfully";
        $result = BatterySize::where('vehicle_model_id',$request->vehicle_model_id)->where('charger_type_id',$request->charger_type_id)->select('name')->first();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function updateVehicleModel(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
            ],
            [
                'name.required' => 'Vehicle Name Is Required',
            ]
        );
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } elseif ($request->make_id == "undefined") {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Vehicle Make Name Is Required"]);
        } else {
            $check_exist = VehicleModel::where('id', '!=', $request->id)->where('vehicle_make_id', $request->make_id)->where('name', $request->name)->first();
            if (!blank($check_exist)) {
                $code = config('constant.UNSUCCESS');
                $msg = "Model Record Already Exist";
            } else {
                $code = config('constant.SUCCESS');
                $msg = "Vehicle Model Data Updated Successfully";
                $vehicle_model = VehicleModel::find($request->id);
                $data = [
                    'vehicle_make_id' => $request->make_id,
                    'name' => $request->name
                ];
                $result = $vehicle_model->update($data);
            }
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function addVehicleModel(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
            ],
            [
                'name.required' => 'Vehicle Name Is Required',
            ]
        );
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } elseif ($request->make_id == "undefined") {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Vehicle Make Name Is Required"]);
        } else {
            $check_exist = VehicleModel::where(function ($query) use ($request) {
                $query->where('vehicle_make_id', $request->make_id)->where('name', $request->name);
            })->first();
            if (!blank($check_exist)) {
                $code = config('constant.UNSUCCESS');
                $msg = "Record Already Exist";
            } else {
                $code = config('constant.SUCCESS');
                $msg = "Vehicle Model Data Created Successfully";
                $data = [
                    'vehicle_make_id' => $request->make_id,
                    'name' => $request->name
                ];
                $result = VehicleModel::create($data);
            }
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function AddOrUpdateUserVehicle(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'vehicle_make_id' => 'required',
                'vehicle_model_id' => 'required',
                'battery_size' => 'required',
                'charger_type' => 'required'
            ],
            [
                'vehicle_make_id.required' => 'Make Name is required',
                'vehicle_model_id.required' => 'Model Name is required',
                'battery_size.required' => 'Battery size is required',
                'charger_type.required' => 'Charger type is required',
            ]
        );
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } elseif ($request->vehicle_make_id == "undefined") {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Vehicle Make Name Is Required"]);
        } elseif ($request->vehicle_model_id == "undefined") {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Vehicle Model Name Is Required"]);
        } else {
            if($request->mark_as_primary){
                $get_all_vehicle = UserVehicle::where('user_id', $request->user_id)->get();
                if(!blank($get_all_vehicle)){
                    foreach($get_all_vehicle as $data){
                        $data->update(['mark_as_primary' => 0]);
                    }
                }
            }else{
                $check_mark_as_primary = UserVehicle::where('user_id', $request->user_id)->where('mark_as_primary','=',1)->first();
                if(blank($check_mark_as_primary)){
                    $request->mark_as_primary = 1;
                }
            }
            if (!blank($request->id)) {
                $check_vehicle = UserVehicle::where('id', "!=", $request->id)->where('vehicle_make_id', $request->vehicle_make_id)->where('vehicle_model_id', $request->vehicle_model_id)->where(function ($q) use ($request) {
                    if (!blank($request->registration_number) || $request->registration_number > 0) {
                        $q->where('registration_number', $request->registration_number);
                    }
                })->where('user_id', $request->user_id)->first();
            } else {
                $check_vehicle = UserVehicle::where('vehicle_make_id', $request->vehicle_make_id)->where('vehicle_model_id', $request->vehicle_model_id)->where(function ($q) use ($request) {
                    if (!blank($request->registration_number) || $request->registration_number > 0) {
                        $q->where('registration_number', $request->registration_number);
                    }
                })->where('user_id', $request->user_id)->first();
            }


            $data = [
                'vehicle_make_id' => $request->vehicle_make_id,
                'vehicle_model_id' => $request->vehicle_model_id,
                'user_id' => $request->user_id,
                'registration_number' => $request->registration_number ? $request->registration_number : null,
                'battery_size' => $request->battery_size ? $request->battery_size : null,
                'charger_type' => $request->charger_type ? $request->charger_type : null,
                'mark_as_primary' => $request->mark_as_primary ? $request->mark_as_primary : 0,
            ];

            if (!blank($check_vehicle)) {
                $code = config('constant.UNSUCCESS');
                $msg = "Data Already Exist To This User";
            } else {
                if (!blank($request->id)) {
                    $result = UserVehicle::find($request->id)->update($data);
                    $code = config('constant.SUCCESS');
                    $msg = "User Vehicle Updated Successfully";
                } else {
                    $result = UserVehicle::create($data);
                    $code = config('constant.SUCCESS');
                    $msg = "User Vehicle Created Successfully";
                }
            }
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function showUserVechile(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Show User Vehicle Data Successfully";
        $user_vehicle = UserVehicle::where('id', $request->id)->with('vehicleMakes', 'vehicleModels','chargerType')->first();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $user_vehicle]);
    }

    public function deleteUserVehicle(Request $request)
    {
        $user_vehicle = UserVehicle::where('id', $request->id);
        $result = $user_vehicle->delete();
        $code = config('constant.SUCCESS');
        $msg = "Delete User Vehicle Successfully";
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function vehicleMakeExport()
    {
        $export_data = $this->exportService->vehicleMakeExport();
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'vehicle-make.csv');
    }

    public function vehcileModelExport()
    {
        $export_data = $this->exportService->vehicleModelExport();
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'vehicle-model.csv');
    }
}
