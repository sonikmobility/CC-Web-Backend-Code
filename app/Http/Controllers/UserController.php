<?php

namespace App\Http\Controllers;

use Validator;
use App\Http\Models\Role;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Services\UserService;
use App\Http\Services\ExportService;
use App\Http\Models\UserVehicle;
use App\Http\Services\DeviceTokenService;
use App\Http\Services\CommonService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentralExport;
use App\Http\Models\Setting;

class UserController extends Controller
{

    public function __construct(UserService $userService, DeviceTokenService $deviceTokenService, CommonService $commonService, ExportService $exportService)
    {
        $this->userService = $userService;
        $this->deviceTokenService = $deviceTokenService;
        $this->commonService = $commonService;
        $this->exportService = $exportService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $item = [];
        $file = '';

        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users,email|email',
            'password' => 'required',
            'name' => 'nullable|string',
            'devicetoken' => 'required',
            'devicetype' => 'required',
            'unique_id' => 'required',
            'mobile_number' => 'required|unique:users,mobile_number|numeric|digits:10',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $user = $this->userService->getUser(['email' => $request->email, 'mobile_number' => $request->mobile_number])->first();
                if ($user) {
                    $code = config('constant.UNSUCCESS');
                    $msg = "User is already exists";
                } else {
                    if ($request->hasFile('profile_image') && isset($request->profile_image)) {
                        //$file = $this->commonService->uploadImage(100, 100,$request->file('profile_image'), 'User/', 'user');
                        $file = $this->commonService->uploadImage($request->file('profile_image'), 'User');
                    }
                    $data = $request->only('email', 'name', 'mobile_number') + ['password' => Hash::make($request->password), 'profile_image' => $file];
                    $add_user = $this->userService->store($data);

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
                    $item->profile_image = $item->profile_image_path . $item->profile_image;

                    $defaultRoleSlug = config('hydra.default_user_role_slug', 'user');
                    $add_user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());

                    $code = config('constant.SUCCESS');
                    $msg = "User register succesfully";
                }

                return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
            } catch (Exception $e) {
                return response()->json(['code' => $code, 'msg' => $e->getMessage()]);
            }
        }
    }

    /**
     * Authenticate an user and dispatch token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $item = (object) [];

        $code = config('constant.UNSUCCESS');
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $role = isset($request->role) ? 1 : 2;
                $user = $this->userService->getUserRoleByEmail($request->email, $role);

                if (!$user || !Hash::check($request->password, $user->password)) {
                    $msg = "Invalid credentials";
                } else {
                    if ($user->status != 1) {
                        $msg = 'Account not active';
                    } else {
                        // if (config('hydra.delete_previous_access_tokens_on_login', true)) {
                        //     // $user->tokens()->delete();
                        // }
                        $item = $this->userService->getUserDetails($user->id);
                        $item->profile_image = $item->admin_profile_image_path . 'original/' . $item->profile_image;
                        $roles = $user->roles->pluck('slug')->all();
                        $plainTextToken = $user->createToken('Api Token', $roles)->plainTextToken;
                        $item['token'] = $plainTextToken;

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
                        $msg = "Login successfully";
                    }
                }
                return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
            } catch (Exception $e) {
                return response()->json(['code' => $code, 'msg' => $e->getMessage()]);
            }
        }
    }

    public function loginWithMobileNumber(Request $request)
    {
        $item = (object) [];

        $code = config('constant.UNSUCCESS');
        $validator = Validator::make($request->all(), [
            'number' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $role = isset($request->role) ? 1 : 2;
                $user = $this->userService->getUserByMobileNumber($request->number, $role);

                if (!$user) {
                    $msg = "Mobile Number Not Exists";
                } else {
                    if ($user->status != 1) {
                        $msg = 'Account not active';
                    } else {
                        // if (config('hydra.delete_previous_access_tokens_on_login', true)) {
                        //     $user->tokens()->delete();
                        // }
                        $item = $this->userService->getUserDetails($user->id);
                        $item->profile_image = $item->profile_image_path . $item->profile_image;
                        $roles = $user->roles->pluck('slug')->all();
                        $plainTextToken = $user->createToken('Api Token', $roles)->plainTextToken;
                        $item['token'] = $plainTextToken;

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
                    }
                }
                return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
            } catch (Exception $e) {
                return response()->json(['code' => $code, 'msg' => $e->getMessage()]);
            }
        }
    }

    public function logout(Request $request)
    {
        if (!auth()->user()) {
            return response(array('code' => 200, 'msg' => "Logout"));
        }
        $user = auth()->user();
        $user_ability = $user->tokens()->where('tokenable_id', $user->id)->first()->abilities[0];
        $validator = Validator::make($request->all(), [
            'devicetoken' => [
                function ($attribute, $value, $fail) use ($user_ability) {
                    if ($user_ability != "admin" && $user_ability != '' && $value == "") {
                        $fail('The ' . $attribute . ' is required.');
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {

            try {
                if ($user_ability != 'admin') {
                    $devicetoken = $this->deviceTokenService->getDeviceToken([['devicetoken', $request->devicetoken], ['user_id', '=', $user->id], ['type', 'user']]);
                    if (count($devicetoken) > 0) {
                        $this->deviceTokenService->deleteDeviceToken(['devicetoken' => $request->devicetoken, 'user_id' => $user->id]);
                        $user->tokens()->delete();
                        $code = config('constant.SUCCESS');
                        $msg = "Logout successfully";
                    } else {
                        $code = config('constant.UNSUCCESS');
                        $msg = "User not found";
                    }
                } else {
                    $user->tokens()->delete();
                    $code = config('constant.SUCCESS');
                    $msg = "Logout successfully";
                }

                return response(array('code' => $code, 'msg' => $msg));
            } catch (Exception $e) {
                return response(array('code' => $code, 'msg' => $msg, 'result' => $e->getMessage()));
            }
        }
    }

    public function changePassword(Request $request)
    {
        $item = (object) [];
        $code = config('constant.UNSUCCESS');
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $user = auth()->user();
                $check_user = $this->userService->getUser(['id' => $user->id])->first();
                if (!Hash::check($request->old_password, $check_user->password)) {
                    $msg = 'Incorrect old password';
                } else {
                    $code = config('constant.SUCCESS');
                    $msg = 'Password changed successfully';
                    $newpassword = Hash::make($request->password);
                    $data = ['password' => $newpassword];
                    $this->userService->updateUser($user->id, $data);
                }

                return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
            } catch (Exception $e) {
                return response(array('code' => $code, 'msg' => $msg, 'result' => $e->getMessage()));
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Http\Models\User  $user
     * @return \App\Http\Models\User  $user
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Http\Models\User  $user
     * @return User
     *
     * @throws MissingAbilityException
     */
    public function add(Request $request){
        $item = (object) [];
        $code = config('constant.UNSUCCESS');
        $user = auth()->user();
        $id = $user->id;

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'unique:users,email',
            'mobile_number' => 'unique:users,mobile_number',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else{
            try {
                $role = isset($request->role) ? 1 : 2;
                $status = $request->status != "undefined" ? $request->status : 1;
                $check_user = $this->userService->getUser(['id' => $id])->first();
                if (!empty($check_user)) {
                    if ($request->image != '' && isset($request->image)) {
                        if ($role == 1) {
                            $file = $this->commonService->getMovedFile($request->image, 'media/Admin/original/', 'Admin');
                        } else {
                            $file = $this->commonService->getMovedFile($request->image, 'media/User/original/', 'User');
                        }
                        $data = $request->only('email', 'first_name', 'last_name', 'mobile_number') + ['profile_image' => $file, 'status' => $status];
                    } else {
                        $data = $request->only('email', 'first_name', 'last_name', 'mobile_number') + ['status' => $status];
                    }
                    $item = $this->userService->Store($data);
                    
                    // add roles
                    $defaultRoleSlug = config('hydra.default_user_role_slug', 'user');
                    $item->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());
                    // add roles

                    $item->profile_image = $item->profile_image_path . $item->profile_image;
                    $code = config('constant.SUCCESS');
                    $msg = 'Profile Add successfully';
                }else{
                    $msg = "User not found";
                }
                return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
            }catch(Exception $e){
                return response(array('code' => $code, 'msg' => $msg, 'result' => $e->getMessage()));
            }
        }
    }

    public function update(Request $request)
    {
        $item = (object) [];
        $code = config('constant.UNSUCCESS');
        if ($request->id != '' && $request->id != null) {
            $id = $request->id;
        } else {
            $user = auth()->user();
            $id = $user->id;
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'unique:users,email,' . $id,
            'mobile_number' => 'unique:users,mobile_number,' . $id,
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            try {
                $role = isset($request->role) ? 1 : 2;
                $status = $request->status != "undefined" ? $request->status : 1;
                $check_user = $this->userService->getUser(['id' => $id])->first();
                if (!empty($check_user)) {
                    if ($request->hasFile('profile_image') && isset($request->profile_image)) {
                        $delete_image = $this->commonService->deleteImage($check_user->profile_image, 'User');
                        $file = $this->commonService->uploadImage($request->file('profile_image'), 'User');
                        //$file = $this->commonService->uploadImage(100, 100,$request->file('profile_image'), 'User/', 'user');
                        $data = $request->only('email', 'first_name', 'last_name', 'mobile_number') + ['profile_image' => $file, 'status' => $status];
                    } elseif ($request->image != '' && isset($request->image)) {
                        if ($role == 1) {
                            $file = $this->commonService->getMovedFile($request->image, 'media/Admin/original/', 'Admin');
                        } else {
                            $file = $this->commonService->getMovedFile($request->image, 'media/User/original/', 'User');
                        }
                        $data = $request->only('email', 'first_name', 'last_name', 'mobile_number') + ['profile_image' => $file, 'status' => $status];
                    } else {
                        $data = $request->only('email', 'first_name', 'last_name', 'mobile_number') + ['status' => $status];
                    }
                    $item = $this->userService->updateUser($check_user->id, $data);
                    if(!blank($request->settings)){
                        $settings = json_decode($request->settings);
                        foreach($settings as $key => $value){
                            // $data = [
                            //     'name' => $key,
                            //     'updated_value' => $value,
                            // ];
                            Setting::updateOrCreate(['name' => $key],['updated_value' => ($value == "" ? 0 : $value)]);
                        }
                    }
                    $item->profile_image = $item->profile_image_path . $item->profile_image;
                    $code = config('constant.SUCCESS');
                    $msg = 'Profile update successfully';
                } else {
                    $msg = "User not found";
                }
                return response(array('code' => $code, 'msg' => $msg, 'result' => $item));
            } catch (Exception $e) {
                return response(array('code' => $code, 'msg' => $msg, 'result' => $e->getMessage()));
            }
        }
    }

    public function getUserList(Request $request)
    {
        $order_by = $request->orderBy ? $request->orderBy : "Desc";
        $sort_by = $request->sortBy ? $request->sortBy : "id";
        $per_page = $request->perPage ? $request->perPage : "5";

        $item = $this->userService->userListing($order_by, $request->search, $sort_by, $request->status, $per_page);
        return response(array('code' => config('constant.SUCCESS'), 'msg' => 'UserListing', 'result' => $item));
    }

    public function getAllUser(Request $request){
        $users = $this->userService->getAllUser();
        return response(array('code' => config('constant.SUCCESS'), 'msg' => 'User', 'result' => $users));
    }
    public function getUser(Request $request)
    {
        if ($request->id != '' && $request->id != null) {
            $item = $this->userService->getUser(['id' => base64_decode($request->id)])->first();
        } else {
            $user = auth()->user();
            $item = $this->userService->getUser(['id' => $user->id])->first();
        }
        return response(array('code' => config('constant.SUCCESS'), 'msg' => 'User', 'result' => $item));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $userRoles = $user->roles;

        if ($userRoles->contains($adminRole)) {
            //the current user is admin, then if there is only one admin - don't delete
            $numberOfAdmins = Role::where('slug', 'admin')->first()->users()->count();
            if (1 == $numberOfAdmins) {
                return response(['error' => 1, 'message' => 'Create another admin before deleting this only admin user'], 409);
            }
        }

        $user->delete();

        return response(['error' => 0, 'message' => 'user deleted']);
    }

    /**
     * Return Auth user
     *
     * @param  Request  $request
     * @return mixed
     */
    public function me(Request $request)
    {
        return $request->user();
    }

    public function removeTmpImage(Request $request)
    {
        $deleteFiles = [];
        $data = $request->all();
        foreach ($data['file'] as $file) {
            $file = $this->commonService->deleteImage($file, 'Temp');
            array_push($deleteFiles, $file);
        }
        return response(array('code' => config('constant.SUCCESS'), 'msg' => 'Success', 'deleted' => $deleteFiles));
    }

    public function uploadTmpImage(Request $request)
    {
        $extension = $request->file->getClientOriginalExtension();
        if ($extension !== 'csv' && $extension !== 'zip' && $extension !== 'xlsx' && $extension !== 'xls' && $extension !== 'pem') {

            $file = $this->commonService->uploadImage($request->file, 'Temp');
        } else {
            ini_set('max_execution_time', 0);
            ini_set('max_input_time', 6400);
            ini_set('memory_limit', '256M');

            $file = $this->commonService->uploadImageInLocal($request->file, 'Temp');
        }

        return response(array('code' => config('constant.SUCCESS'), 'msg' => 'User', 'file' => $file, 'base_url' => config('constant.temp_img_path')));
    }

    public function deleteUser(Request $request)
    {
        $check_user = $this->userService->getUser(['id' => base64_decode($request->id)])->first();
        if (!empty($check_user)) {

            if ($check_user->profile_image != '') {
                $file = $this->commonService->deleteImage($check_user->profile_image, 'User');
            }

            $token = $this->deviceTokenService->getDeviceToken(['user_id' => $check_user->id, 'type' => 'user'])->first();
            if ($token) {
                $this->deviceTokenService->deleteDeviceToken(['user_id' => $check_user->id, 'type' => 'user']);
            }
            $check_user->tokens()->delete();
            $delete = $this->userService->deleteUser(['id' => base64_decode($request->id)]);

            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'User deleted successfully', 'result' => $delete));
        }
    }

    public function deleteUserImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $this->commonService->deleteImage($request->file, 'User');
            $user = $this->userService->getUser(['profile_image' => $request->file, 'id' =>  base64_decode($request->id)])->first();
            if ($user) {
                $user->profile_image = "";
                $user->save();
            }
            return response()->json(['code' => config('constant.SUCCESS'), 'msg' => 'Banner image deleted successfully', 'deleted' => true]);
        }
    }

    public function blockUserById(Request $request)
    {
        $user = $this->userService->getuser(['id' => base64_decode($request->id)])->first();
        $block = $this->userService->updateUser(base64_decode($request->id), ['status' => 0]);
        if ($block) {
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'User blocked successfully', 'result' => $block));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => []));
        }
    }

    public function unblockUserById(Request $request)
    {
        $user = $this->userService->getuser(['id' => base64_decode($request->id)])->first();
        $block = $this->userService->updateUser(base64_decode($request->id), ['status' => 1]);
        if ($block) {
            return response(array('code' => config('constant.SUCCESS'), 'msg' => 'User unblocked successfully', 'result' => $block));
        } else {
            return response(array('code' => config('constant.UNSUCCESS'), 'msg' => 'Something went wrong', 'result' => []));
        }
    }

    public function deleteAdminImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'msg' => $validator->errors()->first()]);
        } else {
            $user = auth()->user();
            $this->commonService->deleteImage($request->file, 'Admin');
            $user = $this->userService->getUser(['profile_image' => $request->file, 'id' =>  base64_decode($user->id)])->first();
            if ($user) {
                $user->profile_image = "";
                $user->save();
            }
            return response()->json(['code' => config('constant.SUCCESS'), 'msg' => 'Banner image deleted successfully', 'deleted' => true]);
        }
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider_name' => 'required',
            'provider_id' => 'required'
        ]);
        $item = [];
        $success = false;
        if ($validator->fails()) {
            return response()->json(['code' => config('constant.UNSUCCESS'), 'success' => $success, 'msg' => $validator->errors()->first()]);
        } else {
            $provider = $request->provider_name;
            $token = $request->access_token;
            $provider_id = $request->provider_id;
            $code = config('constant.UNSUCCESS');
            try {
                $exist_user = User::where('provider_name', $provider)->where('provider_id', $provider_id)->first();
                if ($exist_user == null) {
                    $user = User::create([
                        'provider_name' => $provider,
                        'provider_id' => $provider_id,
                    ]);
                    $item = $this->userService->getUserDetails($user->id);
                    $item->profile_image = $item->profile_image_path . $item->profile_image;
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
                    $success = true;
                    $code = config('constant.SUCCESS');
                    $msg = 'Social Login succesfully Created';
                    return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $item));
                } else {
                    $item = $this->userService->getUserDetails($exist_user->id);
                    $item->profile_image = $item->profile_image_path . $item->profile_image;
                    $roles = $exist_user->roles->pluck('slug')->all();
                    $plainTextToken = $exist_user->createToken('Api Token', $roles)->plainTextToken;
                    $item['token'] = $plainTextToken;
                    if (!blank($item->first_name) && !blank($item->last_name) && !blank($item->email) && !blank($item->mobile_number)) {
                        $item['is_new_user'] = true;
                    } else {
                        $item['is_new_user'] = false;
                    }
                    $check_user_vehicle = UserVehicle::where('user_id', $exist_user->id)->first();
                    if (!blank($check_user_vehicle)) {
                        $item['is_vehicle_added'] = true;
                    } else {
                        $item['is_vehicle_added'] = false;
                    }
                    $success = true;
                    $code = config('constant.SUCCESS');
                    $msg = 'Login succesfully';
                    return response(array('code' => $code, 'success' => $success, 'msg' => $msg, 'result' => $item));
                }
            } catch (Exception $e) {
                $msg = 'Unauthorized';
                return response(array('code' => $code, 'success' => $success, 'msg' => $e->getMessage(), 'result' => $item));
            }
        }
    }

    public function userExport(Request $request)
    {
        $export_data = $this->exportService->usersExport();
        return Excel::download(new CentralExport($export_data['data'], $export_data['header']), 'users.csv');
    }
}
