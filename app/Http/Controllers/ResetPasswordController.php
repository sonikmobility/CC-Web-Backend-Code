<?php

namespace App\Http\Controllers;

use Mail;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Hash;
use App\Http\Services\ResetPasswordService;
use App\Http\Services\CommonService;
use Laravel\Sanctum\Exceptions\MissingAbilityException;

class ResetPasswordController extends Controller
{

    public function __construct(ResetPasswordService $resetPasswordService, UserService $userService, CommonService $commonService)
    {
        $this->resetPasswordService = $resetPasswordService;
        $this->userService = $userService;
        $this->commonService = $commonService;
    }

    public function forgotPassword(Request $request)
    {
        $item = (object) [];
        $code = config('constant.UNSUCCESS');
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $role = isset($request->role) ? 1 : 2;
                $check_user = $this->userService->getUserRoleByEmail($request->email, $role);

                if (!empty($check_user)) {
                    $securecode = $this->commonService->getSecureCode();
                    $deleteToken = $this->resetPasswordService->delete([['user_id', $check_user['id']]]);
                    $save_password = $this->resetPasswordService->store(['user_id' => $check_user['id'], 'token' => $securecode, 'created_at' => date('Y-m-d H:i:s'), 'type' => 'user']);
                    if ($save_password) {
                        $app_logo = 'bucket.jpg';
                        $app_name = 'Sonik Mobility';
                        $logo = config('constant.icon') . $app_logo;
                        $date = date('Y-m-d H:i:s');
                        $title = "Forgot Password";
                        $link = config('constant.Base_V') . "api/resetPassword/" . $securecode;
                        $content = "Hello " . $check_user['name'] . ",<br><br> It seems like you have forgotten your password. Please click the link below to update
                        your password.<br><br>";
                        $content .= "Link: <a href='$link'>" . $link . "</a><br><br>";
                        $content .= "Thanks & Regards,<br> " . $app_name . " Team</center><br><br>";
                        $footer = "Copyright &copy; <?php echo date('Y'); ?> All rights reserved";
                        $data = array();
                        $data['header'] = $title;
                        $data['title'] = $title;
                        $data['img'] = $logo;
                        $data['content'] = $content;
                        $data['date'] = $date;
                        $data['Footer'] = $footer;
                        $from = env('MAIL_USERNAME');

                        Mail::send('email.password_template', ['data' => $data], function ($message) use ($from, $check_user) {
                            $message->from($from, 'Sonik Mobility');
                            $message->subject('Reset Password');
                            $message->to($check_user['email'], "Reset Password");
                        });
                        $code = config('constant.SUCCESS');
                        $msg = 'Mail sent successfully';
                    }
                } else {
                    $code = config('constant.UNSUCCESS');
                    $msg = 'Email not found';
                }
                return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
            } catch (Exception $e) {
                return response()->json(['code' => $code, 'msg' => $e->getMessage()]);
            }
        }
    }

    public function resetPassword(Request $request, $access_code)
    {
        $status = 'TRUE';
        $check_code = $this->resetPasswordService->getToken([['token', $access_code]])->first();

        if (!empty($check_code)) {
            $nowdate = Carbon::now();
            $minutes = Carbon::parse($check_code->created_at)->diffInMinutes(Carbon::now());

            if ($minutes > 1440) {
                $deleteToken = $this->resetPasswordService->delete([['user_id', $check_code['user_id']]]);
                $status = 'Expired';
            } else {
                $check_user = $this->userService->getUser(['id' => $check_code['user_id']])->first();
                $email = $check_user->email;
            }
        } else {
            $status = 'FALSE';
            $email = "";
        }

        if ($request->all()) {
            $check_code = $this->resetPasswordService->getToken([['token', $access_code]])->first();
            if (!empty($check_code)) {
                $data = ['password' => Hash::make($request->password)];
                $update_user = $this->userService->updateUser($check_code['user_id'], $data);
                $deleteToken = $this->resetPasswordService->delete([['token', $access_code]]);
                $status = 'Done';
            } else {
                $status = 'Expired';
            }
        }
        return view('email.reset_password', array('secureCode' => $access_code, 'status' => $status, 'email' => $email));
    }
}
