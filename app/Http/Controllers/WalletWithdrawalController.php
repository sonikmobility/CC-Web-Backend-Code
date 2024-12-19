<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Http\Models\User;
use Illuminate\Http\Request;
use App\Http\Models\ChargerWallet;
use App\Http\Models\WalletHistory;
use App\Http\Services\CommonService;
use App\Http\Models\WalletWithdrawal;
use App\Http\Services\WalletHistoryService;

class WalletWithdrawalController extends Controller
{
    public function __construct(CommonService $commonService,WalletHistoryService $wallet_history_service)
    {
        $this->commonService = $commonService;
        $this->wallet_history_service = $wallet_history_service;
    }

    public function addWalletWithdrawalRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required',
            'account_number' => 'required',
            'ifsc_code' => 'required',
            'account_holder_name' => 'required',
        ]);
        $success = false;
        $result = (object)[];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
        } else {
            $user_exist = auth('sanctum')->user();
            if(!blank($user_exist)){
                $user_id = auth('sanctum')->user()->id;
                $check_request = WalletWithdrawal::where('user_id',$user_id)->where('status','=','0')->first();
                if(!blank($check_request)){
                    $code = config('constant.UNSUCCESS');
                    $msg = "Withdrawal Request Already Pending";
                }else{
                    $check_amount = ChargerWallet::where('user_id',$user_id)->first();
                    $get_setting = $this->commonService->getSettingValue('minimun_wallet_withdraw_amount');

                    if(!blank($check_amount) && ($check_amount->amount >= $get_setting) && ($check_amount->amount > 0)){
                        $data = [
                            'user_id' => $user_id,
                            'bank_name' => $request->bank_name,
                            'account_number' => $request->account_number,
                            'ifsc_code' => $request->ifsc_code,
                            'account_holder_name' => $request->account_holder_name
                        ];
                        $success = true;
                        $code = config('constant.SUCCESS');
                        $msg = "Add Withdrawal Request Successfully";
                        $result = WalletWithdrawal::create($data);
                    }else{
                        $code = config('constant.UNSUCCESS');
                        $msg = "Amount Must be greater than or equal to ₹-".$get_setting;
                    }
                }
            }else {
                $code = config('constant.UNSUCCESS');
                $msg = "User Not Found";
            }
            return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
        }
    }

    public function addUserWithdrawalRequest(Request $request){
        $user_exist = auth('sanctum')->user();
        $result = [];
        $success = false;
        if(!blank($user_exist)){
            $user_id = auth('sanctum')->user()->id;
            $get_data = WalletWithdrawal::select('id','bank_name','account_number','ifsc_code','status','created_at as date')->where('user_id',$user_id)->orderBy('id','desc')->get();
            if(!blank($get_data)){
                foreach($get_data as $key => $data){
                    $get_data[$key]->date = Carbon::parse($data->date)->format("M d Y");
                }
                $success = true;
                $code = config('constant.SUCCESS');
                $msg = "Get Withdrawal Request Successfully";
                $result = $get_data;
            }else{
                $code = config('constant.SUCCESS');
                $msg = "No Data Found";
            }
        }else{
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
    }

    public function getPendingRequest(Request $request){
        $code = config('constant.SUCCESS');
        $msg = 'Get Pending Request Data Successfully';

        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;
        $data = WalletWithdrawal::selectRaw("wallet_withdrawal_request.*, CONCAT(users.first_name,' ',users.last_name) AS users, charger_wallets.amount as amount")
            ->join('users', 'users.id', '=', 'wallet_withdrawal_request.user_id')
            ->leftJoin('charger_wallets','charger_wallets.user_id', '=','wallet_withdrawal_request.user_id')
            ->where('wallet_withdrawal_request.status',"0")
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('wallet_withdrawal_request.bank_name','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.account_number','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.ifsc_code','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.account_holder_name','LIKE', "%{$search}%")->orWhere('users.first_name','LIKE', "%{$search}%")->orWhere('users.last_name','LIKE', "%{$search}%")->orWhere('charger_wallets.amount','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $data));  
    }

    public function withdrawalStatusChange(Request $request){
        $status = $request->status;
        $id = $request->id;

        $get_data = WalletWithdrawal::where('id',$id)->first();
        if($status == 1){
            if(!blank($get_data)){
                $get_amount = ChargerWallet::where('user_id',$get_data->user_id)->first();
                $description = "Withdraw amount of Rs.".$get_amount->amount;
                $this->wallet_history_service->createDebitHistoryForAdmin($get_amount->amount,$description,$get_data->user_id, $request->transaction_id);
                $get_amount->update(['amount'=>0]);
            }
        }
        $update_data = WalletWithdrawal::where('id',$id)->update(['status'=>$status]);
        $code = config('constant.SUCCESS');
        $msg = 'Request Data Updated Successfully';
        return response(array('code' => $code, 'msg' => $msg));
    }

    public function getApprovedRequest(Request $request){
        $code = config('constant.SUCCESS');
        $msg = 'Get Approved Request Data Successfully';

        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;

        $data = WalletWithdrawal::selectRaw("wallet_withdrawal_request.*, CONCAT(users.first_name,' ',users.last_name) AS users, charger_wallets.amount as amount")
            ->join('users', 'users.id', '=', 'wallet_withdrawal_request.user_id')
            ->leftJoin('charger_wallets','charger_wallets.user_id', '=','wallet_withdrawal_request.user_id')
            ->where('wallet_withdrawal_request.status',"1")
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('wallet_withdrawal_request.bank_name','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.account_number','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.ifsc_code','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.account_holder_name','LIKE', "%{$search}%")->orWhere('users.first_name','LIKE', "%{$search}%")->orWhere('users.last_name','LIKE', "%{$search}%")->orWhere('charger_wallets.amount','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $data));
    }

    public function getDeclinedRequest(Request $request){
        $code = config('constant.SUCCESS');
        $msg = 'Get Declined Request Data Successfully';

        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPages ? $request->perPages : "5";
        $search = $request->search;

        $data = WalletWithdrawal::selectRaw("wallet_withdrawal_request.*, CONCAT(users.first_name,' ',users.last_name) AS users, charger_wallets.amount as amount")
            ->join('users', 'users.id', '=', 'wallet_withdrawal_request.user_id')
            ->leftJoin('charger_wallets','charger_wallets.user_id', '=','wallet_withdrawal_request.user_id')
            ->where('wallet_withdrawal_request.status',"2")
            ->where(function($q) use ($search){
                if ($search != '') {
                    $q->where('wallet_withdrawal_request.bank_name','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.account_number','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.ifsc_code','LIKE', "%{$search}%")->orWhere('wallet_withdrawal_request.account_holder_name','LIKE', "%{$search}%")->orWhere('users.first_name','LIKE', "%{$search}%")->orWhere('users.last_name','LIKE', "%{$search}%")->orWhere('charger_wallets.amount','LIKE', "%{$search}%");
                }
            })
            ->orderBy($sort_by, $order_by)
            ->paginate($per_page);
        return response(array('code' => $code, 'msg' => $msg, 'result' => $data));
    }

    public function addWalletAmount(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'transaction_id' => 'required'
        ]);
        $success = false;
        $result = [];
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
        } else {
            $get_user_amount = ChargerWallet::where('user_id',auth('sanctum')->user()->id)->first();
            $create_history = [];
            if(!blank($get_user_amount)){
                $code = config('constant.SUCCESS');
                $msg = 'Money Added Successfully';
                $success = true;
                $amount = $get_user_amount->amount + $request->amount;
                $get_user_amount->update(['amount' => $amount]);
                $description = "Add Wallet Balance";
                $create_history = $this->wallet_history_service->createCreditHistory($request->amount,$description, $request->transaction_id, source : 'phonepe');
            }else{
                $code = config('constant.SUCCESS');
                $msg = 'Something went wrong';
                $success = false;
            }
            return response(array('code' => $code, 'msg' => $msg,'success'=> $success, 'result' => $create_history));
        }
    }

    public function getWalletHistory(Request $request){
        $code = config('constant.SUCCESS');
        $msg = 'Get Wallet History Successfully';
        $user_id = auth('sanctum')->user()->id;
        $success = true;
        $get_history = $this->wallet_history_service->getWalletHistory($user_id);
        return response(array('code' => $code, 'msg' => $msg,'success'=> $success, 'result' => $get_history));
    }

    public function getAllTransactions(Request $request) {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'page' => 'nullable|integer',
            'limit' => 'nullable|integer',
            'sort_by' => 'nullable|string|in:id,amount,type,source,created_at',
            'order_by' => 'nullable|string|in:asc,desc',
        ]);

        $code = config('constant.SUCCESS');
        $msg = 'Fetched wallet transactions successfully';
        $success = true;
        $limit = $request->limit ?? 10;

        $data = [];

        if(isset($request->user_id)){
            $query = WalletHistory::where('user_id', $request->user_id);

            if($request->sort_by) {
                $query->orderBy($request->sort_by, $request->order_by);
            }
            $data = $query->paginate($limit);
            $data = $data->toArray();
            $wallets = ChargerWallet::where('user_id', $request->user_id)->get();
            $data['wallet_balance'] = count($wallets) ? (string) round((float) $wallets[0]->amount, 2) : '0';

            $user = User::where('id', $request->user_id)->first();
            $data['user'] = $user;

        }else{
            $sort_by = $request->input('sort_by', 'id');
            $order_by = $request->input('order_by', 'desc');

            $query = WalletHistory::orderBy($sort_by, $order_by)
            ->with(['users' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'profile_image', 'email');
            }]);

            $data = $query->paginate($limit);
            $data = $data->toArray();

            // Get today's date in the specified timezone
            $current_date = Carbon::now('Asia/Kolkata');
            
            $startOfDay = $current_date->copy()->startOfDay();
            $endOfDay = $current_date->copy()->endOfDay();

            // Convert to UTC
            $startOfDayUTC = $startOfDay->copy()->setTimezone('UTC');
            $endOfDayUTC = $endOfDay->copy()->setTimezone('UTC');

            $totals = WalletHistory::selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) AS total_credit')
            ->selectRaw('SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) AS total_debit')
            ->whereBetween('created_at', [
                $startOfDayUTC->format('Y-m-d H:i:s'),
                $endOfDayUTC->format('Y-m-d H:i:s')
            ])
            ->first();
            
            $data['total_credit'] = isset($totals->total_credit) ? $totals->total_credit : 0;
            $data['total_debit'] = isset($totals->total_debit) ? $totals->total_debit : 0;
        }

        return response([
            'code' => $code, 
            'msg' => $msg,
            'success'=> $success, 
            'result' => $data
        ]);
    }
}
