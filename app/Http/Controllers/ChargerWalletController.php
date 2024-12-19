<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Http\Models\ChargerWallet;
use App\Http\Models\WalletHistory;
use App\Http\Models\WalletWithdrawal;
use App\Http\Services\CommonService;

class ChargerWalletController extends Controller
{
    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getWallet(Request $request){
        $user_exist = auth('sanctum')->user();
        $success = false;
        $result = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $success = true;
            $code = config('constant.SUCCESS');
            $msg = "Wallet Data";
            $result['charger_Wallet'] = ChargerWallet::where('user_id',$user_id)->get();            
            
            if($result['charger_Wallet'] && $result['charger_Wallet'][0] && (int)$result['charger_Wallet'][0]->amount > 0){                
                $amount = $result['charger_Wallet'][0]->amount;
            }else{
                $amount = '0';
            }
            $result['charger_Wallet'][0]->amount = $amount;
            $result['wallet_history'] = WalletHistory::where('user_id',$user_id)->orderBy('id','desc')->get();
            $WalletWithdrawal = WalletWithdrawal::select('bank_name','account_number','ifsc_code','account_holder_name')
                                                ->where('user_id',$user_id)->first();
            $result['WalletWithdrawal'] = $WalletWithdrawal??(object)[];
        }else{
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result));
    }
}
