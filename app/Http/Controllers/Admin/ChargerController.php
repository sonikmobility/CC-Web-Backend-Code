<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\ChargerService;
use Illuminate\Http\Request;
use App\Http\Models\User;
use App\Http\Models\Charger;
use App\Http\Models\Booking;
use Carbon\Carbon;
use Validator;
use App\Http\Services\CommonService;
use App\Http\Services\ExportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentralExport;

class ChargerController extends Controller
{
    public function __construct(ChargerService $charger_service, CommonService $commonService, ExportService $exportService)
    {
        $this->charger_service = $charger_service;
        $this->commonService = $commonService;
        $this->exportService = $exportService;
    }

    public function getUsers(Request $request)
    {
        $user = User::select('id', 'first_name', 'last_name')->whereHas('roles', function ($q) {
            $q->where('role_id', '!=', '1');
        })->get();

        return response(array('code' => config('constant.SUCCESS'), 'msg' => 'UserListing', 'result' => $user));
    }

    public function getAllPublicCharger(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get Public Charger Successfully";
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        if($request->type == "Public"){
            $result = Charger::selectRaw("chargers.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%")->orWhere('city','LIKE', "%{$search}%");
                }
            })
            ->where('is_private', "0")->orderBy($sort_by, $order_by)->paginate($per_page);
        }else{
            $result = Charger::selectRaw("chargers.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%")->orWhere('city','LIKE', "%{$search}%");
                }
            })
            ->where('is_private', "0")->where('user_id',1)->orderBy($sort_by, $order_by)->paginate($per_page);
        }
        
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function getAllPrivateCharger(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get Private Charger Successfully";
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $result = Charger::selectRaw("chargers.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%")->orWhere('city','LIKE', "%{$search}%");
                }
            })
            ->where('is_private', "1")->orderBy($sort_by, $order_by)->paginate($per_page);
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function showPublicCharger(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Show Public Charger Data";
        $result = Charger::with('users')->where('id', $request->id)->first();
        if ($result) {
            $result->start_time = Carbon::parse($result->start_time)->format('H:i:s');
            $result->end_time = Carbon::parse($result->end_time)->format('H:i:s');
            return response(array('code' => $code, 'msg' => $msg, 'result' => $result));
        } else {
            return response(array('code' => $code, 'msg' => $msg, 'result' => $result));
        }
    }

    public function deleteCharger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:chargers,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $charger = $this->charger_service->deleteCharger($request->id);
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Charger Details Deleted Successfully'));
        }
    }

    public function addOrUpdateCharger(Request $request)
    {
        if ($request->is_private == 0) // For a public Charger
        {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'name' => 'required',
                'type' => 'required',
                'address' => 'required',
                'city' => 'required',
                'code' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'price' => 'required',
                'uuid' => 'required',
                'charge_type' => 'nullable',
                'charger_speed' => 'nullable',
                'connector_type' => 'nullable'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
            } else {
                if (!blank($request->image) && $request->image != '' && isset($request->image)) {
                    $file = $this->commonService->getMovedFile($request->image, 'media/QR/original/', 'QR');
                } else {
                    $file = null;
                }
                $data = [
                    'is_private' => $request->is_private,
                    'name' => $request->name,
                    'type' => $request->type,
                    'address' => $request->address,
                    'city' => $request->city,
                    'zip_code' => $request->code,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'start_time' => $request->start_time ? $request->start_time : null,
                    'end_time' => $request->end_time ? $request->end_time : null,
                    'image' => $file,
                    'user_id' => $request->user_id,
                    'price' => $request->price,
                    'uuid' => $request->uuid,
                    'status' => $request->status,
                    'charge_type' => $request->charge_type ?? '',
                    'charger_speed' => $request->charger_speed ?? '',
                    'connector_type' => $request->connector_type ?? '0',
                ];
                if (!blank($request->id)) {
                    $code = config('constant.SUCCESS');
                    $msg = 'Charger Updated Successfully';
                    $check_uuid = Charger::where('id', '!=', $request->id)->where('uuid', $request->uuid)->first();
                    if (!blank($check_uuid)) {
                        return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'UUID Number Already Exist', 'result' => []));
                    }
                    $charger = $this->charger_service->updateCharger($request->id, $data);
                    if ($charger) {
                        return response(array('code' => $code, 'msg' => $msg, 'result' => $charger));
                    } else {
                        return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $charger));
                    }
                } else {
                    $code = config('constant.SUCCESS');
                    $msg = 'Charger Created Successfully';
                    $check_uuid = Charger::where('uuid', $request->uuid)->first();
                    if (!blank($check_uuid)) {
                        return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'UUID Number Already Exist', 'result' => []));
                    }
                    $store_charger = $this->charger_service->storeCharger($data);
                    return response(array('code' => $code, 'msg' => $msg, 'result' => $store_charger));
                }
            }
        } else // For Private Charger
        {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'name' => 'required',
                'type' => 'required',
                'address' => 'required',
                'city' => 'required',
                'code' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'uuid' => 'required',
                'charge_type' => 'nullable',
                'charger_speed' => 'nullable',
                'connector_type' => 'nullable',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
            } else {
                if (!blank($request->image) && $request->image != '' && isset($request->image)) {
                    $file = $this->commonService->getMovedFile($request->image, 'media/QR/original/', 'QR');;
                } else {
                    $file = null;
                }
                $data = [
                    'is_private' => $request->is_private,
                    'name' => $request->name,
                    'type' => $request->type,
                    'address' => $request->address,
                    'city' => $request->city,
                    'zip_code' => $request->code,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'image' => $file,
                    'user_id' => $request->user_id,
                    'uuid' => $request->uuid,
                    'charge_type' => $request->charge_type ?? '',
                    'charger_speed' => $request->charger_speed ?? '',
                    'connector_type' => $request->connector_type ?? '0',
                ];
                if (!blank($request->id)) {
                    $code = config('constant.SUCCESS');
                    $msg = 'Charger Updated Successfully';
                    $check_uuid = Charger::where('id', '!=', $request->id)->where('uuid', $request->uuid)->first();
                    if (!blank($check_uuid)) {
                        return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'UUID Number Already Exist', 'result' => []));
                    }
                    $charger = $this->charger_service->updateCharger($request->id, $data);
                    if ($charger) {
                        return response(array('code' => $code, 'msg' => $msg, 'result' => $charger));
                    } else {
                        return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $charger));
                    }
                } else {
                    $code = config('constant.SUCCESS');
                    $msg = 'Charger Created Successfully';
                    $check_uuid = Charger::where('uuid', $request->uuid)->first();
                    if (!blank($check_uuid)) {
                        return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'UUID Number Already Exist', 'result' => []));
                    }
                    $store_charger = $this->charger_service->storeCharger($data);
                    return response(array('code' => $code, 'msg' => $msg, 'result' => $store_charger));
                }
            }
        }
    }

    public function publicChargerExport()
    {
        $charger_type = 'public';
        $export_data = $this->exportService->chargersExport($charger_type);
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), $charger_type . '-charger.csv');
    }

    public function sonikChargerExport(){
        $charger_type = 'sonik';
        $export_data = $this->exportService->chargersExport($charger_type);
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), $charger_type . '-charger.csv');
    }

    public function privateChargerExport()
    {
        $charger_type = 'private';
        $export_data = $this->exportService->chargersExport($charger_type);
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), $charger_type . '-charger.csv');
    }

    public function underMaintenanceChargerExport(){
        $charger_status = 3;
        $export_data = $this->exportService->chargerStatusExport($charger_status);
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Under-maintenance-charger.csv');
    }

    public function unAvailableChargerExport()
    {
        $charger_status = 1;
        $export_data = $this->exportService->chargerStatusExport($charger_status);
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Unavailable-charger.csv');
    }

    public function availableChargerExport()
    {
        $charger_status = 0;
        $export_data = $this->exportService->chargerStatusExport($charger_status);
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Available-charger.csv');
    }

    public function busyChargerExport()
    {
        $charger_status = 2;
        $export_data = $this->exportService->chargerStatusExport($charger_status);
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Busy-charger.csv');
    }

    public function getAllChargersData(){
        $code = config('constant.SUCCESS');
        $msg = 'Get All Charger Data Successfully';
        $data = Charger::select('id','is_private','zip_code','status','name','address','city','longitude','latitude')->whereHas('users',function($q){
                $q->whereNull('deleted_at');
            })->whereNotNull('latitude')->whereNotNull('longitude')->get();
        return response(array('code' => $code, 'msg' => $msg, 'result' => $data));
    }
}
