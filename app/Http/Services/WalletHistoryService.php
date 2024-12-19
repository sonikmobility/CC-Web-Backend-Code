<?php

namespace App\Http\Services;
use App\Http\Models\WalletHistory;

class WalletHistoryService
{
    public function createCreditHistory($amount,$description, $transaction_id = null, $source = ''){
        $validation = $this->validateAmount($amount);
        if ($validation !== true) {
            return $validation;
        }

        $creation_data = [
            'user_id' => auth('sanctum')->user()->id,
            'amount' => $amount,
            'type' => 'credit',
            'transaction_id' => $transaction_id,
            'source' => $source,
            'description' => $description,
        ];
        return WalletHistory::create($creation_data);
    }

    public function createDebitHistory($amount,$description, $transaction_id = null, $source = ''){
        $validation = $this->validateAmount($amount);
        if ($validation !== true) {
            return $validation;
        }

        $creation_data = [
            'user_id' => auth('sanctum')->user()->id,
            'amount' => $amount,
            'type' => 'debit',
            'transaction_id' => $transaction_id,
            'source' => $source,
            'description' => $description,
        ];
        return WalletHistory::create($creation_data);
    }

    public function createDebitPhonepeHistory($amount,$description, $transaction_id = null){
        $validation = $this->validateAmount($amount);
        if ($validation !== true) {
            return $validation;
        }

        $creation_data = [
            'user_id' => auth('sanctum')->user()->id,
            'amount' => $amount,
            'type' => 'debit',
            'transaction_id' => $transaction_id,
            'source' => 'phonepe',
            'description' => $description,
        ];
        return WalletHistory::create($creation_data);
    }

    public function createDebitHistoryForAdmin($amount,$description,$user_id, $transaction_id = null){
        $validation = $this->validateAmount($amount);
        if ($validation !== true) {
            return $validation;
        }

        $creation_data = [
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => 'debit',
            'transaction_id' => $transaction_id,
            'description' => $description,
        ];
        return WalletHistory::create($creation_data);
    }

    public function createCreditHistoryForAdmin($amount,$description,$user_id, $transaction_id = null){
        $validation = $this->validateAmount($amount);
        if ($validation !== true) {
            return $validation;
        }

        $creation_data = [
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => 'credit',
            'transaction_id' => $transaction_id,
            'description' => $description,
        ];
        return WalletHistory::create($creation_data);
    }

    public function getWalletHistory($user_id){
        return WalletHistory::where('user_id',$user_id)->get();
    }

    public function validateAmount($amount){
        if ($amount <= 0) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => 'Amount Cannot Be 0']);
        }
        return true;
    }
    
}