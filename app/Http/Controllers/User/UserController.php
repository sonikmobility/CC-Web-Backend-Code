<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Validator;
use DB;
use App\Http\Models\Role;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Services\UserService;
use App\Http\Services\DeviceTokenService;
use App\Http\Services\CommonService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use App\Http\Models\UserVehicle;
use Illuminate\Support\Facades\Mail;
use App\Mail\Signup;
use App\Http\Models\ChargerWallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    public function __construct(UserService $userService, DeviceTokenService $deviceTokenService, CommonService $commonService)
    {
        $this->userService = $userService;
        $this->deviceTokenService = $deviceTokenService;
        $this->commonService = $commonService;
    }

    public function loginWithMobileNumber(Request $request){
        $item = (object) [];
        $code = config('constant.UNSUCCESS');
        $success = false;
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required',
            'devicetoken' => 'required',
            'devicetype' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $role = isset($request->role) ? 1 : 2;
                $user = $this->userService->getUserByMobileNumber($request->mobile_number, $role);
                if (!blank($user)) {
                    if ($user->status != 1) {
                        $msg = 'Account not active';
                    }else{
                        $item = $this->userService->getUserDetails($user->id);
                        $item->profile_image = $item->api_profile_image_path . $item->profile_image;
                        $roles = $user->roles->pluck('slug')->all();
                        $plainTextToken = $user->createToken('Api Token', $roles)->plainTextToken;
                        $item['token'] = $plainTextToken;
                        if (!blank($item->first_name) && !blank($item->last_name) && !blank($item->mobile_number)) {
                            $item['is_new_user'] = true;
                        } else {
                            $item['is_new_user'] = false;
                        }
                        $check_user_vehicle = UserVehicle::where('user_id', $user->id)->first();
                        if (!blank($check_user_vehicle)) {
                            $item['is_vehicle_added'] = true;
                        } else {
                            $item['is_vehicle_added'] = false;
                        }
                        if (isset($request->devicetoken) && $request->devicetoken != '') {
                            $token = $this->deviceTokenService->getDeviceToken(['devicetoken' => $request->devicetoken, 'type' => 'user'])->first();
                            if (!$token) {
                                $token_data = $request->only('devicetoken', 'devicetype', 'unique_id') + ['user_id' => $user->id];
                                $this->deviceTokenService->addDeviceToken($token_data);
                            } else {
                                $this->deviceTokenService->updateDeviceToken($token->id, ['user_id' => $user->id]);
                            }
                        }
                        $code = config('constant.SUCCESS');
                        $msg = "Login succesfully";
                        $success = true;
                    }
                }else{
                    $file = '';
                    if ($request->hasFile('profile_image') && isset($request->profile_image)) {
                        $file = $this->commonService->uploadImage($request->file('profile_image'), 'User');
                    }
                    $data = $request->only('mobile_number') + ['profile_image' => $file];
                    $add_user = $this->userService->store($data);
                    $add_wallet_data = ChargerWallet::create(['user_id'=>$add_user->id, 'amount' => 0]);
                    if ($request->devicetoken != '' && $request->devicetype != '') {
                        $token = $this->deviceTokenService->getDeviceToken(['devicetoken' => $request->devicetoken, 'type' => 'user'])->first();
                        if (!$token) {
                            $token_data = $request->only('devicetoken', 'devicetype', 'unique_id') + ['user_id' => $add_user->id];
                            $this->deviceTokenService->addDeviceToken($token_data);
                        }
                    }
                    $item = $this->userService->getUserDetails($add_user->id);
                    if (!blank($item->first_name) && !blank($item->last_name) && !blank($item->mobile_number)) {
                        $item['is_new_user'] = true;
                    } else {
                        $item['is_new_user'] = false;
                    }
                    $roles = $add_user->roles->pluck('slug')->all();
                    $plainTextToken = $add_user->createToken('Api Token', $roles)->plainTextToken;
                    $item['token'] = $plainTextToken;
                    $item->profile_image = $item->api_profile_image_path . $item->profile_image;

                    $check_user_vehicle = UserVehicle::where('user_id', $add_user->id)->first();
                    if (!blank($check_user_vehicle)) {
                        $item['is_vehicle_added'] = true;
                    } else {
                        $item['is_vehicle_added'] = false;
                    }
                    $defaultRoleSlug = config('hydra.default_user_role_slug', 'user');
                    $add_user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());

                    $code = config('constant.SUCCESS');
                    $msg = "User register succesfully";
                    $success = true;
                }
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $item));
            }catch(Exception $e){
                return response()->json(['code' => $code, 'success' => $success, 'msg' => $e->getMessage()]);
            }
        }
    }

    public function loginWithMobileNumberOLD(Request $request)
    {
        $item = (object) [];
        $code = config('constant.UNSUCCESS');
        $success = false;
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required',
            'devicetoken' => 'required',
            'devicetype' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $role = isset($request->role) ? 1 : 2;
                $user = $this->userService->getUserByMobileNumber($request->mobile_number, $role);
                if (!blank($user)) {
                    $file = '';
                    if ($request->hasFile('profile_image') && isset($request->profile_image)) {
                        $file = $this->commonService->uploadImage($request->file('profile_image'), 'User');
                    }
                    $check_mobile_number = User::where('mobile_number',$request->mobile_number)->first();
                    if(!blank($check_mobile_number)){
                        $code = config('constant.UNSUCCESS');
                        $msg = "Mobile Number has already taken";
                        $success = false;
                        $item = (object)[];
                    }else{
                        $data = $request->only('mobile_number') + ['profile_image' => $file];
                        $add_user = $this->userService->store($data);
                        $add_wallet_data = ChargerWallet::create(['user_id'=>$add_user->id, 'amount' => 0]);
                        if ($request->devicetoken != '' && $request->devicetype != '') {
                            $token = $this->deviceTokenService->getDeviceToken(['devicetoken' => $request->devicetoken, 'type' => 'user'])->first();
                            if (!$token) {
                                $token_data = $request->only('devicetoken', 'devicetype', 'unique_id') + ['user_id' => $add_user->id];
                                $this->deviceTokenService->addDeviceToken($token_data);
                            } else {
                                $this->deviceTokenService->updateDeviceToken($token->id, ['id' => $add_user->id]);
                            }
                        }
                        $item = $this->userService->getUserDetails($add_user->id);
                        if (!blank($item->first_name) && !blank($item->last_name) && !blank($item->email) && !blank($item->mobile_number)) {
                            $item['is_new_user'] = true;
                        } else {
                            $item['is_new_user'] = false;
                        }
                        $roles = $add_user->roles->pluck('slug')->all();
                        $plainTextToken = $add_user->createToken('Api Token', $roles)->plainTextToken;
                        $item['token'] = $plainTextToken;
                        $item->profile_image = $item->api_profile_image_path . $item->profile_image;

                        $check_user_vehicle = UserVehicle::where('user_id', $add_user->id)->first();
                        if (!blank($check_user_vehicle)) {
                            $item['is_vehicle_added'] = true;
                        } else {
                            $item['is_vehicle_added'] = false;
                        }
                        $defaultRoleSlug = config('hydra.default_user_role_slug', 'user');
                        $add_user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());

                        $code = config('constant.SUCCESS');
                        $msg = "User register succesfully";
                        $success = true;
                    }
                } else {
                    if ($user->status != 1) {
                        $msg = 'Account not active';
                    } else {
                        // if (config('hydra.delete_previous_access_tokens_on_login', true)) {
                        //     $user->tokens()->delete();
                        // }
                        $item = $this->userService->getUserDetails($user->id);
                        $item->profile_image = $item->api_profile_image_path . $item->profile_image;
                        $roles = $user->roles->pluck('slug')->all();
                        $plainTextToken = $user->createToken('Api Token', $roles)->plainTextToken;
                        $item['token'] = $plainTextToken;
                        if (!blank($item->first_name) && !blank($item->last_name) && !blank($item->email) && !blank($item->mobile_number)) {
                            $item['is_new_user'] = true;
                        } else {
                            $item['is_new_user'] = false;
                        }
                        $check_user_vehicle = UserVehicle::where('user_id', $user->id)->first();
                        if (!blank($check_user_vehicle)) {
                            $item['is_vehicle_added'] = true;
                        } else {
                            $item['is_vehicle_added'] = false;
                        }
                        if (isset($request->devicetoken) && $request->devicetoken != '') {
                            $token = $this->deviceTokenService->getDeviceToken(['devicetoken' => $request->devicetoken, 'type' => 'user'])->first();
                            if (!$token) {
                                $token_data = $request->only('devicetoken', 'devicetype', 'unique_id') + ['user_id' => $user->id];
                                $this->deviceTokenService->addDeviceToken($token_data);
                            } else {
                                $this->deviceTokenService->updateDeviceToken($token->id, ['user_id' => $user->id]);
                            }
                        }
                        $code = config('constant.SUCCESS');
                        $msg = "Login succesfully";
                        $success = true;
                    }
                }
                return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $item));
            } catch (Exception $e) {
                return response()->json(['code' => $code, 'success' => $success, 'msg' => $e->getMessage()]);
            }
        }
    }

    public function updateUserInfo(Request $request)
    {
        $user_exist = auth('sanctum')->user();
        $result = (object)[];
        $success = false;
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'nullable|email',
                'mobile_number' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
            } else {
                try {
                    $data = [
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $request->email,
                        'mobile_number' => $request->mobile_number,
                    ];
                    $user = User::find($user_id);
                    if ($request->hasFile('image')) {
                        if(!blank($user->profile_image)){
                            $delete_previous_image = $this->deleteImageInFolder($user->profile_image, 'User');
                        }
                        $image = $request->file('image');
                        $file = $this->uploadImage($image, 'User');
                        $update_user_image = User::where('id',$user->id)->update(['profile_image' => $file]);
                    }
                    $data_update = $user->update($data);
                    if ($data_update) {
                        if (!blank($user->first_name) && !blank($user->last_name) && !blank($user->email) && !$user->register_mail) {
                            $email = $user->email;
                            $mailData = [
                                'full_name' => $user->full_name,
                            ];
                            Mail::to($email)->send(new Signup($mailData));
                            $user->update(['register_mail' => 1]);
                        }
                        $result = $this->userService->getUserDetails($user->id);
                        $check_user_vehicle = UserVehicle::where('user_id', $user->id)->first();
                        if (!blank($check_user_vehicle)) {
                            $result['is_vehicle_added'] = true;
                        } else {
                            $result['is_vehicle_added'] = false;
                        }
                        $result['token'] = $request->bearerToken();
                        $code = config('constant.SUCCESS');
                        $msg = "User Info Updated Successfully";
                        $success = true;
                    }
                } catch (Exception $e) {
                    $code = config('constant.UNSUCCESS');
                    $msg = $e->getMessage();
                }
            }
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $result]);
    }

    public function getUserDetails(Request $request)
    {
        $user_exist = auth('sanctum')->user();
        $success = false;
        $user_data = [];
        if (!blank($user_exist)) {
            $user_data = auth('sanctum')->user();
            if (!blank($user_data->first_name) && !blank($user_data->last_name) && !blank($user_data->mobile_number)) {
                $user_data['is_new_user'] = true;
            } else {
                $user_data['is_new_user'] = false;
            }
            $check_user_vehicle = UserVehicle::where('user_id', $user_data->id)->first();
            $token = $request->bearerToken();
            if (!blank($check_user_vehicle)) {
                $user_data['is_vehicle_added'] = true;
            } else {
                $user_data['is_vehicle_added'] = false;
            }
            $user_data['token'] = $token;
            $user_data->profile_image = $user_data->api_profile_image_path . $user_data->profile_image;
            $success = true;
            $code = config('constant.SUCCESS');
            $msg = "User Found";
        } else {
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $user_data]);
    }

    public function deleteAccount(Request $request){
        $user_exist = auth('sanctum')->user();
        $success = false;
        $user_data = [];
        if (!blank($user_exist)) {
            $user_id = auth('sanctum')->user()->id;
            $get_user = $this->userService->getUserDetails($user_id);
            if(!blank($get_user)){
                // $get_user->delete();
                User::where('id', $user_id)->update(['deleted_at' => Carbon::now('Asia/Kolkata')]);
                $code = config('constant.SUCCESS');
                $msg = "Account Delete Successfully";
                $success = true;
            }else{
                $code = config('constant.UNSUCCESS');
                $msg = "Account Already Deleted";
            }
            
            
        }else{
            $code = config('constant.UNSUCCESS');
            $msg = "User Not Found";
        }
        return response()->json(['code' => $code, 'success' => $success, 'msg' => $msg]);
    }

    public function deleteImageInFolder($filename, $folder, $is_original = '')
    {
        $filepath = $folder . '/original/' . $filename;
        $thum_path = $folder . '/thumb/' . $filename;

        if ($is_original == true) {
            if(File::exists($filepath) || File::exists($thum_path)) 
            { 
                File::delete($filepath);
                File::delete($thum_path);
                return true;
            }
        } else {
            if (File::exists(public_path("media/{$folder}/original/{$filename}"))) {
                return File::delete(public_path("media/{$folder}/original/{$filename}"));
            }
        }
        return false;
    }

    public function uploadImage($image,$folder) {
        $fileContent = file_get_contents($image);
        $createfilename = $folder.'_'.time().rand(0000, 9999);
        $extension = $image->getClientOriginalExtension();
        $filename  = $createfilename.'.'.$extension;

        if(!File::exists(public_path('media/'.$folder.'//original/'))){
            $path = base_path('media/' . $folder . '/original/');
            File::makeDirectory($path, 0777, true, true);
        }

        $image->move(public_path('media/'.$folder.'//original/'), $filename);
        $path = public_path("media/Temp/original/".$filename);

        return $filename;  
    }
}
