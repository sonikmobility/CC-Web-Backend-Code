<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:chargers,name',
                'address' => 'required',
                'city' => 'required',
                'zip_code' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'uuid_number' => 'required|unique:chargers,uuid'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $code = config('constant.SUCCESS');
                $msg = 'Charger Created Successfully';
                $success = true;
                if (!blank($request->image) && $request->image != '' && isset($request->image)) {
                    $file = $this->commonService->getMovedFile($request->image, 'media/QR/original/', 'QR');
                } else {
                    $file = null;
                }
                $time = Carbon::now();
                $start = Carbon::create($time->year, $time->month, $time->day, 6, 0, 0);
                $end = Carbon::create($time->year, $time->month, $time->day, 23, 59, 0);
                $data = [
                    'name' => $request->name,
                    'address' => $request->address,
                    'city' => $request->city,
                    'zip_code' => $request->zip_code,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'image' => $file,
                    'user_id' => $user_id,
                    'uuid' => $request->uuid_number,
                    'is_private' => 0,
                    'price' => 20,
                    'start_time' => $start,
                    'end_time' => $end,
                    'status' => 0,
                ];
                $result = $this->charger_service->storeCharger($data);
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }

        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
    }

    public function getChargerByUserId(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            //$result = $this->charger_service->getCharger(['user_id' => $user_id]);
            if (!blank($request->latitude) && $request->longitude) {
                $result = Charger::selectRaw("*, 111.111 *
                DEGREES(ACOS(LEAST(1.0, COS(RADIANS(latitude))
                 * COS(RADIANS({$request->latitude}))
                 * COS(RADIANS(longitude - {$request->longitude}))
                 + SIN(RADIANS(latitude))
                 * SIN(RADIANS({$request->latitude}))))) as distanceValue")
                    ->where('user_id', $user_id)
                    ->orderBy('distanceValue', 'ASC')
                    ->get();
                foreach ($result as $key => $charger) {
                    $result[$key]->distance = number_format($charger->distanceValue, 1) . ' km away';
                    $result[$key]->start_time = date('h:i a', strtotime($charger->start_time));
                    $result[$key]->end_time = date('h:i a', strtotime($charger->end_time));
                }
                $result->sortBy('distanceValue')->values();
            }
            if ($result) {
                $success = true;
                return response(array('code' => config('constant.SUCCESS'), 'success' => $success, 'msg' => 'Charger details By User ID', 'result' => $result));
            } else {
                return response(array('code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => 'Something went wrong', 'result' => $result));
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
            return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
        }
    }

    public function getChargerDetail(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'charger_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $charger = $this->charger_service->getCharger(['id' => $request->charger_id, 'user_id' => $user_id])->first();
                if ($charger) {
                    $charger->start_time = $charger->start_time ? date('h:i a', strtotime($charger->start_time)) : null;
                    $charger->end_time = $charger->end_time ? date('h:i a', strtotime($charger->end_time)) : null;
                    $success = true;
                    return response(array('code' => config('constant.SUCCESS'), 'success' => $success, 'msg' => 'Charger detail', 'result' => $charger));
                } else {
                    return response(array('code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => 'Something went wrong', 'result' => $charger));
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
            return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
        }
    }

    public function manageMyCharger(Request $request)
    {
        $success = false;
        $user_exist = auth('sanctum')->user();
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'is_private' => 'required|boolean',
                'charger_id' => 'required',
                'start_time' => 'required_if:is_private,0',
                'end_time' => 'required_if:is_private,0',
                'price' => 'required_if:is_private,0',
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $charger = $this->charger_service->getCharger(['user_id' => $user_id, 'id' => $request->charger_id]);
                if (!blank($charger)) {
                    $data = [
                        'is_private' => $request->is_private,
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time,
                        'price' => $request->price
                    ];
                    $result = $this->charger_service->updateCharger($request->charger_id, $data);
                    $result->start_time = date('h:i a', strtotime($result->start_time));
                    $result->end_time = date('h:i a', strtotime($result->end_time));
                    // $result = $this->charger_service->getCharger(['id' => $request->charger_id]);
                    // foreach ($result as $key => $chargers) {
                    //     $result[$key]->start_time = Carbon::parse($chargers->start_time)->format('h:m a');
                    //     $result[$key]->end_time = Carbon::parse($chargers->end_time)->format('h:m a');
                    // }
                    $code = config('constant.SUCCESS');
                    $msg = "Charger Updated SuccessFully";
                    $success = true;
                } else {
                    $code = config('constant.UNSUCCESS');
                    $msg = "Charger Details Not Found";
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
    }
}
