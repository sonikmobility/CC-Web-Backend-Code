<?php

namespace App\Http\Services;

use App\Http\Models\User;
use App\Http\Models\DeviceToken;
use App\Http\Models\UserRole;

class UserService
{
    public function getUser($where)
    {
        return User::where($where)->get();
    }

    public function getUserDetails($user_id)
    {
        $user_detail =  User::where('id', $user_id)->first();
        $devicetoken = DeviceToken::where('user_id', $user_id)->where('type', 'user')->orderby('id', 'desc')->first();
        if (!empty($user_detail)) {
            if (!empty($devicetoken)) {
                $user_detail->devicetoken = $devicetoken->devicetoken;
            } else {
                $user_detail->devicetoken = "";
            }

            return $user_detail;
        }
    }

    public function Store($data)
    {
        return User::create($data);
    }

    public function updateUser($user_id, $data)
    {
        User::where('id', $user_id)->update($data);
        return User::where('id', $user_id)->first();
    }

    //public function userListing($search,$status = "1",$sortby = "created_at",$orderby = "desc", $total_record = 50)
    public function userListing($orderby = "desc", $search, $sortby = "created_at", $status = 1, $total_record = 50)
    {
        $user = User::selectRaw("users.*, CONCAT(users.first_name,' ',users.last_name) AS users")
            ->whereHas('roles', function ($q) {
                $q->where('role_id', '!=', '1');
            })
            ->where(function ($q) use ($search) {
                if ($search != '') {
                    $q->where(\DB::raw('CONCAT(first_name, " ", last_name)'),'LIKE',"%{$search}%");
                    $q->Orwhere('mobile_number', 'LIKE', "%{$search}%");
                    $q->Orwhere('email', 'LIKE', "%{$search}%");
                }
            })->where(function ($q) use ($status) {
                if ($status != '') {
                    $q->where('status', $status);
                }
            })
            // ->whereNull('deleted_at')
            ->orderBy($sortby, $orderby)
            ->paginate($total_record);

        return $user;
    }

    public function getUserRoleByEmail($email, $role)
    {
        $user = User::whereHas('roles', function ($q) use ($role) {
            $q->where('role_id', $role);
        })
            ->where(function ($q) use ($email) {
                $q->where('email', $email);
            })->first();

        return $user;
    }

    public function getUserByMobileNumber($number, $role)
    {
        $user = User::whereHas('roles', function ($q) use ($role) {
            $q->where('role_id', $role);
        })
            ->where(function ($q) use ($number) {
                $q->where('users.mobile_number', $number);
            })->whereNull('deleted_at')->first();
        // $user = User::with(['roles'=>function($query) use($role){
        //         $query->where('role_id', $role);
        // }])->where('users.mobile_number',$number)->first();
        return $user;
    }

    public function deleteUser($id)
    {
        return User::where('id', $id)->delete();
    }

    public function getAllUser(){
        $user = User::selectRaw("users.*, CONCAT(users.first_name,' ',users.last_name) AS fullname")
            ->whereHas('roles', function ($q) {
                $q->where('role_id', '!=', '1');
            })->orderBy('users.id','desc')->get();
        return $user;
    }
}
