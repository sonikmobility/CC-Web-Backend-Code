<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\ChargerService;
use Validator;
use Carbon\Carbon;
use App\Http\Models\Charger;
use App\Http\Services\CommonService;

class ChargerController extends Controller
{
    public function __construct(ChargerService $charger_service, CommonService $commonService)
    {
        $this->charger_service = $charger_service;
        $this->commonService = $commonService;
    }

    public function addCharger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'is_private' => 'required|boolean',
            'address' => 'required',
            'city' => 'required',
            'zip_code' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'start_time' => 'required_if:is_private,0',
            'end_time' => 'required_if:is_private,0'
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            // $existing_charger = $this->charger_service->checkChargerExist($request->all());
            // if(!blank($existing_charger)){
            // 	$code = config('constant.SUCCESS');
            // 	$msg = 'Charger Already Exist';
            // 	$store_charger = [];

            // }else{
            $code = config('constant.SUCCESS');
            $msg = 'Charger Created Successfully';
            if (!blank($request->image) && $request->image != '' && isset($request->image)) {
                $file = $this->commonService->getMovedFile($request->image, 'media/QR/original/', 'QR');;
            } else {
                $file = null;
            }

            $data = [
                'is_private' => $request->is_private,
                'address' => $request->address,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'start_time' => $request->start_time ? $request->start_time : null,
                'end_time' => $request->end_time ? $request->end_time : null,
                'image' => $file,
                'user_id' => $request->user_id,
                'price' => $request->price
            ];
            $store_charger = $this->charger_service->storeCharger($data);
            // }
            return response(array('code' => $code, 'msg' => $msg, 'result' => $store_charger));
        }
    }

    public function getCharger(Request $request)
    {
        $charger = $this->charger_service->getCharger(['id' => $request->charger_id])->first();
        if ($charger) {
            $charger->start_time = Carbon::parse($charger->start_time)->format('H:i:s');
            $charger->end_time = Carbon::parse($charger->end_time)->format('H:i:s');
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Charger detail', 'result' => $charger));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $charger));
        }
    }

    public function getChargerByUserId(Request $request)
    {
        $charger = $this->charger_service->getCharger(['user_id' => $request->user_id]);
        if ($charger) {
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Charger details By User ID', 'result' => $charger));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $charger));
        }
    }

    public function getNearByCharger(Request $request)
    {
        $success = false;
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
        } else {
            $charger = $this->charger_service->getChargerNearByAddress($request->latitude, $request->longitude);
            if ($charger) {
                $success = true;
                return response(array('code' => config('constant.SUCCESS'), 'success' => $success, 'msg' => 'Near By Charger Details', 'result' => $charger));
            } else {
                return response(array('code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => 'No Record Found', 'result' => $charger));
            }
        }
    }

    public function getAllCharger()
    {
        return Charger::all();
    }


    public function updateCharger(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:chargers,id',
            'start_time' => 'required_if:is_private,0',
            'end_time' => 'required_if:is_private,0'
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            if ($request->image != '' && isset($request->image)) {
                $file = $this->commonService->getMovedFile($request->image, 'media/QR/original/', 'QR');
            }
            $data = [
                'is_private' => $request->is_private,
                'address' => $request->address,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'start_time' => $request->start_time ? $request->start_time : null,
                'end_time' => $request->end_time ? $request->end_time : null,
                'image' => ($file ? $file : null),
                'price' => $request->price
            ];

            $charger = $this->charger_service->updateCharger($request->id, $data);
            if ($charger) {
                return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Charger Details Updated Successfully', 'result' => $charger));
            } else {
                return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => $charger));
            }
        }
    }

    public function deleteCharger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charger_id' => 'required|exists:chargers,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $charger = $this->charger_service->deleteCharger($request->charger_id);
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Charger Details Deleted Successfully'));
        }
    }

    public function deleteQrCodeImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $this->commonService->deleteImage($request->file, 'QR');
            $user = $this->charger_service->getCharger(['image' => $request->file, 'id' => $request->id])->first();
            if ($user) {
                $user->image = "";
                $user->save();
            }
            return response()->json(['code' => config('constant.SUCCESS'), 'msg' => 'QR image deleted successfully', 'deleted' => true]);
        }
    }

    public function getBusyCharger(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Busy Charger Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "10";
        $search = $request->search;
        $get_busy_charger = Charger::selectRaw("chargers.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->whereNotNull('chargers.start_time')
            ->whereNotNull('chargers.end_time')
            ->where('chargers.status', 2)
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%")->orWhere('city','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_busy_charger));
    }

    public function getAvailableCharger(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Available Charger Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "10";
        $search = $request->search;
        $get_available_charger = Charger::selectRaw("chargers.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->whereNotNull('chargers.start_time')
            ->whereNotNull('chargers.end_time')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%")->orWhere('city','LIKE', "%{$search}%");
                }
            })
            ->where('chargers.status', 0)
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_available_charger));
    }

    public function getUnAvailableCharger(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Unavailable Charger Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "10";
        $search = $request->search;
        $get_unavailabe_charger = Charger::selectRaw("chargers.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->whereNotNull('chargers.start_time')
            ->whereNotNull('chargers.end_time')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%")->orWhere('city','LIKE', "%{$search}%");
                }
            })
            ->where('chargers.status', 1)
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_unavailabe_charger));
    }

    public function getUnderMaintenanceCharger(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = 'Get Under Maintenance Charger Data Successfully';
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "10";
        $search = $request->search;
        $get_under_maintenance_charger = Charger::selectRaw("chargers.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->whereNotNull('chargers.start_time')
            ->whereNotNull('chargers.end_time')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%")->orWhere('city','LIKE', "%{$search}%");
                }
            })
            ->where('chargers.status', 3)
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_under_maintenance_charger));
    }

    public function getChargerById(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'charger_id' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Charger Data";
                $result = Charger::selectRaw("chargers.*, DATE_FORMAT(chargers.start_time, '%l:%i %p') as start_time, DATE_FORMAT(chargers.end_time, '%l:%i %p') as end_time, chargers.latitude as latitude, chargers.longitude as longitude ,CONCAT(ROUND(111.111 *
                DEGREES(ACOS(LEAST(1.0, COS(RADIANS(chargers.latitude))
                 * COS(RADIANS({$request->latitude}))
                 * COS(RADIANS(chargers.longitude - {$request->longitude}))
                 + SIN(RADIANS(chargers.latitude))
                 * SIN(RADIANS({$request->latitude}))))),2),' km') as distance")
                    ->where('id', $request->charger_id)
                    ->get();
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
            return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
        }
    }
}
