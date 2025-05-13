<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Http\Models\Booking;

class WebHookController extends Controller
{
    public function getWebHookBookingStatus(Request $request){
        
        $data = $request->all();
        
        \Log::channel('webhook')->info('webhook_response',['response'=>$data]);
        
        $api_key = env('RAZORPAY_API_KEY') ? env('RAZORPAY_API_KEY') : "rzp_test_xefZhgKfU24o7b";
        $api_secret = env('RAZORPAY_API_SECRET_KEY') ? env('RAZORPAY_API_SECRET_KEY') : "Ht1kRUVkdl2Py9aup7tkaZQn";

        $api = new Api($api_key,$api_secret);
        if(!blank($data)){
            $razorpay_order_id = $data['payload']['payment']['entity']['order_id'];
            //$razorpay_order_id = "order_LMApbtNtxrMvLV";

            $get_booking = Booking::where(function($q) use ($razorpay_order_id){
                $q->where('razorpay_pre_order_id',$razorpay_order_id)->orWhere('razorpay_final_order_id',$razorpay_order_id);
            })->first();
            
            if(!blank($get_booking))
            {
                // Condition for Booking is cancel or not
                if($get_booking->is_cancel == 0 && $get_booking->cancelled_time == null)
                {
                    // For a Final Payment
                    if(!blank($get_booking->pre_auth_transaction_id) && $get_booking->payment_status == "Pre")
                    {
                        $get_order_status =  $api->order->fetch($get_booking->razorpay_final_order_id)->payments();
                        $get_order_status['items'] = array_reverse($get_order_status['items']);
                        foreach ($get_order_status['items'] as $keys => $values) {

                            // Add Log
                            \Log::channel('webhook')->info('webhook_final_response',['oder_id'=>$get_booking->razorpay_final_order_id,'payment_id'=>$get_order_status['items'][$keys]['id'],'order_status'=>$get_order_status['items'][$keys]['status']]);

                            if ($get_order_status['items'][$keys]['status'] == 'authorized') {
                                // Update transaction id 
                                Booking::where('id',$get_booking->id)->update(['final_transaction_id'=>$get_order_status['items'][$keys]['id'],'payment_status'=>'Completed']);

                                // For capturing a payment
                                $api->payment->fetch($get_order_status['items'][$keys]['id'])->capture(array('amount'=>$get_order_status['items'][$keys]['amount']));

                                $message="You have new order request.";
                                $message = urlencode($message);
                            }elseif($get_order_status['items'][$keys]['status'] == 'captured'){
                                // Update transaction id 
                                Booking::where('id',$get_booking->id)->update(['final_transaction_id'=>$get_order_status['items'][$keys]['id'],'payment_status'=>'Completed']);
                                $message="You have new order request.";
                                $message = urlencode($message);
                            }else{
                                $message="Payment Failed";
                                $message = urlencode($message);
                                Booking::where('id',$get_booking->id)->update(['final_transaction_id'=>$get_order_status['items'][$keys]['id'],'payment_status'=>'Failed']);
                            }
                        }
                    }
                    // For a Pre Payment
                    elseif(($get_booking->pre_auth_transaction_id == null) && ($get_booking->payment_status == "Pending"))
                    {
                        $get_order_status =  $api->order->fetch($get_booking->razorpay_pre_order_id)->payments();
                        $get_order_status['items'] = array_reverse($get_order_status['items']);
                        foreach ($get_order_status['items'] as $keys => $values) {

                            // Add Log
                            \Log::channel('webhook')->info('webhook_pre_auth_response',['oder_id'=>$get_booking->razorpay_pre_order_id,'payment_id'=>$get_order_status['items'][$keys]['id'],'order_status'=>$get_order_status['items'][$keys]['status']]);

                            if ($get_order_status['items'][$keys]['status'] == 'authorized') {

                                // Update transaction id  
                                Booking::where('id',$get_booking->id)->update(['pre_auth_transaction_id'=>$get_order_status['items'][$keys]['id'],'payment_status'=>'Pre']);

                                // For capturing a payment
                                $api->payment->fetch($get_order_status['items'][$keys]['id'])->capture(array('amount'=>$get_order_status['items'][$keys]['amount']));

                                $message="You have new order request.";
                                $message = urlencode($message);
                            }elseif($get_order_status['items'][$keys]['status'] == 'captured'){

                                // Update transaction id  
                                Booking::where('id',$get_booking->id)->update(['pre_auth_transaction_id'=>$get_order_status['items'][$keys]['id'],'payment_status'=>'Pre']);
                                $message="You have new order request.";
                                $message = urlencode($message);
                            }else{
                                Booking::where('id',$get_booking->id)->update(['pre_auth_transaction_id'=>$get_order_status['items'][$keys]['id'],'payment_status'=>'Failed']);
                                $message="Payment Failed";
                                $message = urlencode($message);
                            }
                        }
                    }
                    else
                    {
                        $message="Something went wrong";
                        $message = urlencode($message);
                    }
                }else{
                    $message="Order Is Already Cancelled";
                    $message = urlencode($message);
                }
            }
        }else{
            echo "No Request data found";
        }
    }
}
