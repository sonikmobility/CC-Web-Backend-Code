<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Http\Models\UserReport;
use App\Http\Models\ReportTitle;

class UserReportController extends Controller
{
    public function index(Request $request){
        $result = [];
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $get_user_reports = UserReport::selectRaw("user_reports.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->join('users', 'users.id', '=', 'user_reports.user_id')
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('user_reports.reason','LIKE', "%{$search}%")->orWhere('users.first_name','LIKE', "%{$search}%")->orWhere('users.last_name','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)->paginate($per_page);
        $code = config('constant.SUCCESS');
        $msg = 'Get User Reports Successfully';
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_user_reports));
    }

    public function store(Request $request){
        $user_exist = auth('sanctum')->user();
        $success = false;
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'reason' => 'required',
                'description' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "User Report Created";
                $data = [
                    'user_id' => $user_id,
                    'report_reason' => $request->reason,
                    'description' => $request->description
                ];
                $create_report = UserReport::create($data);
            }
        }else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg]);
    }

    public function getTitle(Request $request){
        $result = [];
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $get_report_title = ReportTitle::where(function($q) use ($search){
                if ($search != '') {
                    $q->where('name','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)->paginate($per_page);
        $code = config('constant.SUCCESS');
        $msg = 'Get Report Title Successfully';
        return response(array('code' => $code, 'msg' => $msg, 'result' => $get_report_title));
    }

    public function addReportTitle(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:report_titles,name',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Report Title Created Successfully";
            $data = [
                'name' => $request->name
            ];
            $result = ReportTitle::create($data);
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function showReportTitle(Request $request){
        $code = config('constant.SUCCESS');
        $msg = "Show Report Title Data";
        $result = ReportTitle::select('id', 'name')->where('id', $request->id)->first();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function updateReportTitle(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $code = config('constant.SUCCESS');
            $msg = "Report Title Updated Successfully";
            $check_title = ReportTitle::where('id', '!=', $request->id)->where('name', $request->name)->first();
            if (!blank($check_title)) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => "Title Already Exist", 'result' => $result]);
            }
            $title = ReportTitle::find($request->id);
            $data = [
                'name' => $request->name
            ];
            $result = $title->update($data);
        }
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function deleteReportTitle(Request $request){
        $code = config('constant.SUCCESS');
        $msg = "Delete Report Title Data Successfully";
        $result = ReportTitle::find($request->id)->delete();
        return response()->json(['code' => $code, 'msg' => $msg, 'result' => $result]);
    }

    public function getReason(Request $request){
        $get_report_title = ReportTitle::get();
        $code = config('constant.SUCCESS');
        $msg = 'Get Report Title Successfully';
        $success = true;
        return response(array('code' => $code, 'msg' => $msg,'success'=> $success,'result' => $get_report_title));
    }
}
