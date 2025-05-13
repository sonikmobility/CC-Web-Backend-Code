<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Models\Booking;
use App\Http\Models\User;
use Ixudra\Curl\Facades\Curl;

class PhonePayController extends Controller
{
    public function getWebView(Request $request){
        if((!blank($request->booking_id) || !blank($request->user_id)) && !blank($request->amount)){
            $amount =  $request->amount;
            if(!blank($request->booking_id)){
                $booking_id = $request->booking_id;
                return redirect()->route('payment-process',['booking'=>$booking_id,'amount'=>$amount]);
            }

            if(!blank($request->user_id)){
                $user_id = $request->user_id;
                return redirect()->route('payment-process',['user_id'=>$user_id,'amount'=>$amount]);
            }
            //return view('phone-pay.web-view-page', ['booking_id' => $booking_id,'amount'=>$amount]);
        }
    }

    public function phonePayProcess(Request $request)
    {
        if(!blank($request->amount) && (!blank($request->booking) || !blank($request->user_id))){
            if(!blank($request->user_id)){
                $get_user_details = User::where('id',$request->user_id)->first();
            }
            if(!blank($request->booking)){
                $get_booking = Booking::where('id',$request->booking)->first();
                if(!blank($get_booking)){
                    $get_user_details=User::where('id',$get_booking->user_id)->first();
                }
            }

            $prefix = 'PT';
            $randomNumber = mt_rand(100000000000000, 999999999999999); // Generate a random number with up to 15 digits
            $merchantTransactionId = $prefix . $randomNumber;

            $data = array (
                'merchantId' => env('MERCHANT_ID', "PGTESTPAYUAT"),
                'merchantTransactionId' => $merchantTransactionId,
                'merchantUserId' => 'MUID123',
                'amount' => ($request->amount * 100),
                'redirectUrl' => route('phonepay.response'),
                'redirectMode' => 'POST',
                'callbackUrl' => route('phonepay.response'),
                'mobileNumber' => $get_user_details->mobile_number ? $get_user_details->mobile_number : "",
                'paymentInstrument' => 
                array (
                    'type' => 'PAY_PAGE',
                ),
            );

            $encode = base64_encode(json_encode($data));
    
            $saltKey = env('PHONEPAY_SALT_KEY', "099eb0cd-02cf-4e2a-8aca-3e6c6aff0399");
            $saltIndex = 1;
    
            $string = $encode.'/pg/v1/pay'.$saltKey;
            $sha256 = hash('sha256',$string);
    
            $finalXHeader = $sha256.'###'.$saltIndex;

            $phonepe_base_url = env('PHONEPAY_BASE_URL', 'https://api-preprod.phonepe.com/apis/pg-sandbox');
    
            $response = Curl::to($phonepe_base_url.'/pg/v1/pay')
                    ->withHeader('Content-Type:application/json')
                    ->withHeader('X-VERIFY:'.$finalXHeader)
                    ->withData(json_encode(['request' => $encode]))
                    ->post();
    
            $rData = json_decode($response);
            
            if($rData->success){
                return redirect()->to($rData->data->instrumentResponse->redirectInfo->url);
            }else{
                // return "error"; 
                $code = config('constant.UNSUCCESS');
                $msg = 'Payment Failed';
                $success = false;
                return redirect()->route('phonepay.failure');
            }
        }
    }

    public function phonePayResponse(Request $request)
    {
        $input = $request->all();
        
        $saltKey = env('PHONEPAY_SALT_KEY', "099eb0cd-02cf-4e2a-8aca-3e6c6aff0399");
        $saltIndex = 1;

        $environment = env('env', "DEV");

        if($environment == 'DEV'){
            $input['merchantId'] = 'PGTESTPAYUAT';
            $input['transactionId'] = 'MT7850590068188104';
        }

        $finalXHeader = hash('sha256','/pg/v1/status/'.$input['merchantId'].'/'.$input['transactionId'].$saltKey).'###'.$saltIndex;
        
        $phonepe_base_url = env('PHONEPAY_BASE_URL', 'https://api-preprod.phonepe.com/apis/pg-sandbox');     
        
        $response = Curl::to($phonepe_base_url.'/pg/v1/status/'.$input['merchantId'].'/'.$input['transactionId'])
                ->withHeader('Content-Type:application/json')
                ->withHeader('accept:application/json')
                ->withHeader('X-VERIFY:'.$finalXHeader)
                ->withHeader('X-MERCHANT-ID:'.$input['merchantId'])
                ->get();
        $rData = json_decode($response);
        
        if($rData->success && $rData->code == "PAYMENT_SUCCESS"){
            // $request->session()->put('transactionId', $rData->data->transactionId);
            // $request->session()->put('merchantId', $rData->data->merchantId);
            // $request->session()->put('merchantTransactionId', $rData->data->merchantTransactionId);
            // return $rData->message;
            // $code = config('constant.SUCCESS');
            // $msg = 'Payment SuccessFull';
            // $success = true;
            // return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result'=> $rData->data->transactionId]);
            return redirect()->route('phonepay.success',['transaction_id'=>$rData->data->transactionId]);
        }else{
            $code = config('constant.UNSUCCESS');
            $msg = 'Payment Failed';
            $success = false;
            return redirect()->route('phonepay.failure');
            //return $rData->message;
        }
    }

    public function phonePaySuccess(Request $request){
        $code = config('constant.SUCCESS');
        $msg = 'Payment SuccessFull';
        $success = true;
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result'=> $request->transaction_id]); 
    }

    public function phonePayFailure(Request $request){
        $code = config('constant.UNSUCCESS');
        $msg = 'Payment Failed';
        $success = false;
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg]); 
    }

    public function phonePayRefund(Request $request){
      $data = array (
        'merchantId' => $request->session()->get('merchantId'),
        "merchantUserId" => "MUID123",
        "originalTransactionId" => $request->session()->get('transactionId'),
        'merchantTransactionId' => $request->session()->get('merchantTransactionId'),
        "amount" => 1000,
        "callbackUrl" => route('phonepay.response')
      );
      $encode = base64_encode(json_encode($data));

      $saltKey = env('PHONEPAY_SALT_KEY', "099eb0cd-02cf-4e2a-8aca-3e6c6aff0399");
      $saltIndex = 1;

      $string = $encode.'/pg/v1/refund'.$saltKey;
      $sha256 = hash('sha256',$string);

      $finalXHeader = $sha256.'###'.$saltIndex;
      $phonepe_base_url = env('PHONEPAY_BASE_URL', 'https://api-preprod.phonepe.com/apis/pg-sandbox');


      $response = Curl::to($phonepe_base_url.'/pg/v1/refund')
              ->withHeader('Content-Type:application/json')
              ->withHeader('X-VERIFY:'.$finalXHeader)
              ->withData(json_encode(['request' => $encode]))
              ->post();
      $rData = json_decode($response);
      dd($rData);
    }
}