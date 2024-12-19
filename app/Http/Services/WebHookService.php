<?php

namespace App\Http\Services;
use Razorpay\Api\Api;

class WebHookService
{
	public function createOrderForPrePayment($data){
		$api_key = env('RAZORPAY_API_KEY') ? env('RAZORPAY_API_KEY') : "rzp_test_xefZhgKfU24o7b";
        $api_secret = env('RAZORPAY_API_SECRET_KEY') ? env('RAZORPAY_API_SECRET_KEY') : "Ht1kRUVkdl2Py9aup7tkaZQn";
        if(isset($data['pre_auth_charge'])){
        	//$amount = intval(number_format($data['pre_auth_charge'],2) * 100);
            $amount = ($data['pre_auth_charge'] * 100);
        }else{
        	//$amount = intval(number_format($data['price'],2) * 100);
            $amount = ($data['price'] * 100);
        }
        
        $api = new Api($api_key,$api_secret);
        $create_order = $api->order->create(
        	array(
        		'receipt' => $data['id'],
        		'amount' => $amount, 
        		'currency' => 'INR',
                'notes'=> array('Payment Type'=> 'Pre Payment')
        	)
        );
        \Log::channel('webhook')->info('create_order_response',['request_parameter'=>$data,'response'=>$create_order->id]);
        return $create_order->id;
	}

    public function createOrderForFinalPayment($data){
        $api_key = env('RAZORPAY_API_KEY') ? env('RAZORPAY_API_KEY') : "rzp_test_xefZhgKfU24o7b";
        $api_secret = env('RAZORPAY_API_SECRET_KEY') ? env('RAZORPAY_API_SECRET_KEY') : "Ht1kRUVkdl2Py9aup7tkaZQn";
        if(isset($data['price'])){
            //dd(intval(number_format($data['price'],2) * 100));
            //$amount = intval(number_format($data['price'],2) * 100);
            $amount = ($data['price'] * 100);
        }

        $api = new Api($api_key,$api_secret);
        $create_order = $api->order->create(
            array(
                'receipt' => $data['id'],
                'amount' => $amount, 
                'currency' => 'INR',
                'notes'=> array('Payment Type'=> 'Final Payment')
            )
        );
        \Log::channel('webhook')->info('create_order_response',['request_parameter'=>$data,'response'=>$create_order->id]);
        return $create_order->id;
    }
}