<?php

namespace App\Http\Controllers\Admin;

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
	public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

	public function getAllChargerType(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Get All Charger Type List";
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $result = ChargerType::where(function($q) use ($search){
                if ($search != '') {
                    $q->where('type','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)->paginate($per_page);
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function showChargerType(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Show Charger Type Data";
        $result = ChargerType::where('id', $request->id)->first();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function addChargerType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|unique:charger_type,type',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Charger Type Data Created Successfully";
            $data = [
                'type' => $request->type,
            ];
            $result = ChargerType::create($data);
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function updateChargerType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Charger Type Data Updated Successfully";
            $check_type = ChargerType::where('id', '!=', $request->id)->where('type', $request->type)->first();
            if (!blank($check_type)) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Type Already Exist", 'result' => $result]);
            }
            $charger_type = ChargerType::find($request->id);
            $data = [
                'type' => $request->type,
                'vehicle_model_id' => $request->vehicle_model_id
            ];
            $result = $charger_type->update($data);
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function deleteChargerType(Request $request)
    {
        $code = config('constant.SUCCESS');
        $msg = "Delete Charger Type Data Successfully";
        $result = ChargerType::find($request->id)->delete();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function chargerTypeExport()
    {
        $export_data = $this->exportService->chargerTypeExport();
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'Charger-type.csv');
    }

    public function getChargerTypeList(Request $request){
        $result = ChargerType::select('id','type')->get();
        $code = config('constant.SUCCESS');
        $msg = "Get Charger Types";
        $success = true;
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result]);
    }
}