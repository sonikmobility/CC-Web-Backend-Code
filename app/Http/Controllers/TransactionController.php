<?php

namespace App\Http\Controllers;

use App\Http\Models\Booking;
use App\Http\Models\TransactionHistory;
use App\Http\Models\User;
use App\Jobs\checkTransactionStatusJob;
use App\Utility\CurlRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ixudra\Curl\Facades\Curl;

class TransactionController extends Controller
{

    public function getIntentDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'data' => 'required'
        ]);

        switch ($request->type) {
            case 'booking':
            case 'update_pre_payment_status':
                $validator->addRules([
                    'data.booking_id' => 'required|integer',
                    'data.charge' => 'required',
                    'data.payment_method' => 'required', // 1- wallet, 2 - direct
                    'data.payment_type' => 'required' // pre , direct
                ]);
                break;
            case 'add_wallet_amount':
                $validator->addRules([
                    'data.amount' => 'required'
                ]);
                break;
            case 'store_booking':
                $validator->addRules([
                    'data.charger_station_id' => 'required',
                    'data.start_time' => 'required|date|date_format:Y-m-d H:i',
                    'data.end_time' => 'required|date|after_or_equal:start_time|date_format:Y-m-d H:i',
                    'data.unit_price' => 'required',
                    'data.booking_type' => 'required',
                    'data.update_pre_payment_data' => 'required|array',
                    'data.update_pre_payment_data.charge' => 'required',
                    'data.update_pre_payment_data.payment_method' => 'required',
                    'data.update_pre_payment_data.payment_type' => 'required'
                ]);
                break;
            case 'online_payment':
                $validator->addRules([
                    'data.user_id' => 'required',
                    'data.payment_amount' => 'required',
                    'data.session_id' => 'required'
                ]);
                break;
            default:
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => 'Invalid transaction type']);
        }

        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => $validator->errors()->first()]);
        }

        if (!blank($request->amount) && (!blank($request->booking) || !blank($request->user_id))) {
            if (!blank($request->user_id)) {
                $get_user_details = User::where('id', $request->user_id)->first();
            }
            if (!blank($request->booking)) {
                $get_booking = Booking::where('id', $request->booking)->first();
                if (!blank($get_booking)) {
                    $get_user_details = User::where('id', $get_booking->user_id)->first();
                }
            }

            $merchantTransactionId = 'MT' . substr(uniqid(), -13);

            $callbackUrl = env('API_URL') . 'phonepe/handle-callback';

            if (env('ENV') == "DEV") {
                $merchantTransactionId = 'MT7850590068188104';
            }

            $request_amount = (int) round(floatval($request->amount) * 100);

            if ($get_user_details && isset($get_user_details->mobile_number) && strlen($get_user_details->mobile_number) > 10) {
                $get_user_details->mobile_number = substr($get_user_details->mobile_number, 3);
            }

            $data = [
                'merchantId' => env('MERCHANT_ID'),
                'merchantTransactionId' => $merchantTransactionId,
                'merchantUserId' => $get_user_details->id ? (string) $get_user_details->id : "",
                'amount' => $request_amount,
                'callbackUrl' => $callbackUrl,
                'mobileNumber' => $get_user_details->mobile_number ? $get_user_details->mobile_number : "",
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE'
                ],
            ];

            $base64body = base64_encode(json_encode($data));

            $saltKey = env('PHONEPAY_SALT_KEY');
            $saltIndex = env('PHONEPAY_SALT_INDEX');

            $string = $base64body . '/pg/v1/pay' . $saltKey;
            $sha256 = hash('sha256', $string);

            $finalXHeader = $sha256 . '###' . $saltIndex;

            $data = [
                'base64body' => $base64body,
                'checksum' => $finalXHeader,
                'apiEndPoint' => '/pg/v1/pay',
                'merchant_id' => env('MERCHANT_ID'),
                'transaction_id' => $merchantTransactionId
            ];

            $paymentData = [
                'type' => $request->input('type'),
                'transaction_id' => $merchantTransactionId,
                'bearer_token' => $request->bearerToken(),
                'data' => $request->input('data')
            ];

            $cacheKey = 'phonepe_request_data_' . $merchantTransactionId;
            Log::channel('phonepe')->info('intent_details', ['payment_data' => $paymentData]);
            Cache::put($cacheKey, $paymentData);

            return response()->json(['code' => config('constant.SUCCESS'), 'success' => true, 'data' => $data]);
        }
    }

    public function phonePeCallBack(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'response' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => $validator->errors()->first()]);
        }

        // Decode the Base64 encoded response
        $decodedResponse = base64_decode($request->input('response'));

        // Check if decoding was successful
        if ($decodedResponse === false) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => 'Invalid Base64 encoding']);
        }

        $data = json_decode($decodedResponse, true);
        $code = isset($data['code']) ? $data['code'] : null;
        $success = isset($data['success']) ? $data['success'] : null;
        $merchantId = isset($data['data']) ? $data['data']['merchantId'] : null;
        $merchantTransactionId = isset($data['data']) ? $data['data']['merchantTransactionId'] : null;

        if (env('ENV') == 'DEV') {
            $merchantTransactionId = 'MT7850590068188104';
        }

        $cacheKey = 'phonepe_request_data_' . $merchantTransactionId;

        if ($success && $code === 'PAYMENT_SUCCESS') {
            Log::channel('phonepe')->info('payment_success', ['phonepe_response' => $data]);

            if (Cache::has($cacheKey)) {
                $success_response = $this->paymentSuccess($cacheKey, $merchantTransactionId);
                if(isset($success_response['code'])){
                    if($success_response['code'] == config('constant.UNSUCCESS'))
                        return $success_response;

                    return response()->json(['code' => config('constant.SUCCESS'), 'success' => true, 'status' => $code]);
                }else{
                    return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'status' => 'Something Went Wrong']);
                }
            }else{
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'status' => 'Something Went Wrong']);
            }
        } elseif ($success && $code === 'PAYMENT_ERROR') {
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
            Log::channel('phonepe')->info('payment_failed', ['phonepe_response' => $data]);

            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'status' => $code]);
        } else {
            Log::channel('phonepe')->info('payment_pending', ['phonepe_response' => $data]);

            // $this->dispatch(new checkTransactionStatusJob($merchantId, $merchantTransactionId, $cacheData));
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'status' => isset($code) ? $code : 'PAYMENT_PENDING']);
        }
    }

    public function checkTransactionStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|string',
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => $validator->errors()->first()]);
        }

        $input = $request->all();

        $saltKey = env('PHONEPAY_SALT_KEY');
        $saltIndex = env('PHONEPAY_SALT_INDEX');

        $finalXHeader = hash('sha256', '/pg/v1/status/' . $input['merchant_id'] . '/' . $input['transaction_id'] . $saltKey) . '###' . $saltIndex;

        $phonepe_base_url = env('PHONEPAY_BASE_URL');

        $headers = [
            'Content-Type' => 'application/json',
            'accept' => 'application/json',
            'X-VERIFY' => $finalXHeader,
            'X-MERCHANT-ID' => $input['merchant_id']
        ];

        $request_url = $phonepe_base_url . '/pg/v1/status/' . $input['merchant_id'] . '/' . $input['transaction_id'];
        $response = CurlRequest::sendGetRequest($request_url, $headers);

        $rData = json_decode($response);
        Log::channel('phonepe')->info('check_transaction_status', ['phonepe_response' => $rData]);

        $cacheKey = 'phonepe_request_data_' . $input['transaction_id'];

        $code = isset($rData->code) ? $rData->code : null;
        $success = isset($rData->success) ? $rData->success : null;
        
        if ($success && $code == "PAYMENT_SUCCESS") {
            if (Cache::has($cacheKey)) {
                $success_response = $this->paymentSuccess($cacheKey, $input['transaction_id']);

                if($success_response['code'] == config('constant.UNSUCCESS'))
                    return $success_response;
            }

            return response()->json(['code' => config('constant.SUCCESS'), 'success' => true, 'status' => $code ]);
        } elseif (!$success && isset($rData->code)) {
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }

            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'status' => $code]);
        } else {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'status' => 'SOMETHING_WENT_WRONG']);
        }
    }

    private function paymentSuccess(string $cacheKey, string $merchantTransactionId)
    {
        $cacheData = Cache::get($cacheKey);

        $type = $cacheData['type'];
        $bearer_token = isset($cacheData['bearer_token']) ? $cacheData['bearer_token'] : '';

        switch ($type) {
            case 'booking':
                $cacheData['data']['transaction_id'] = $merchantTransactionId;
                $routeName = 'payment.success';
                break;
            case 'update_pre_payment_status':
                $cacheData['data']['transaction_id'] = $merchantTransactionId;
                $routeName = 'update.pre.payment.status';
                break;
            case 'add_wallet_amount':
                $cacheData['data']['transaction_id'] = $merchantTransactionId;
                $routeName = 'add.wallet.amount';
                break;
            case 'store_booking':
                $routeName = 'store.booking';
                break;
            case 'online_payment':
                $apiUrl = env('OCPP_API_URL') . 'online-payment';
                $cacheData['data']['transaction_id'] = $merchantTransactionId;
                $response_details = $this->callOcppRoute($apiUrl, 'POST', $cacheData['data'], $bearer_token);

                if(isset($response_details['code']) && $response_details['code'] == '200')
                    Cache::forget($cacheKey);
                
                return $response_details;
                break;
            default:
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => 'Invalid transaction type']);
        }

        $response_payment = $this->callRoute($routeName, 'POST', $cacheData['data'], $bearer_token);

        if($response_payment['code'] == config('constant.UNSUCCESS')){
            return $response_payment;
        }

        if($type === 'store_booking'){
            $cacheData['data']['update_pre_payment_data']['transaction_id'] = $merchantTransactionId;
            $cacheData['data']['update_pre_payment_data']['booking_id'] = (isset($response_payment['result']) ? (isset($response_payment['result']['id']) ? $response_payment['result']['id'] : null) : null);

            $response_payment = $this->callRoute('update.pre.payment.status', 'POST', $cacheData['data']['update_pre_payment_data'], $bearer_token);

            if($response_payment['code'] == config('constant.UNSUCCESS')){
                return $response_payment;
            }
        }
        
        Cache::forget($cacheKey);
        return $response_payment;
    }

    private function callRoute($routeName, $requestType, $data, $bearer_token){
        $request_payment = Request::create(route($routeName), $requestType, $data);
        $request_payment->headers->set('Authorization', 'Bearer ' . $bearer_token);
        $request_payment->headers->set('Accept', 'application/json');

        $response_payment = app()->handle($request_payment);

        // Check if the response is successful
        if (!$response_payment->isSuccessful()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => false, 'msg' => $response_payment->getStatusCode()]);
        }

        $response_payment = json_decode($response_payment->getContent(), true);
        return $response_payment;
    }

    private function callOcppRoute($apiUrl, $requestType, $data, $bearer_token)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $bearer_token,
                'Accept' => 'application/json',
            ])->{$requestType}($apiUrl, $data);

            if (!$response->successful()) {
                return response()->json([
                    'code' => config('constant.UNSUCCESS'),
                    'success' => false,
                    'msg' => $response->status(),
                ]);
            }

            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'code' => config('constant.UNSUCCESS'),
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
