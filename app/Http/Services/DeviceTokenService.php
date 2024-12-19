<?php

namespace App\Http\Services;
use App\Http\Models\DeviceToken;

class DeviceTokenService
{
    public function deleteDeviceToken($where)
    {
        return DeviceToken::where($where)->delete();
    }

    public function addDeviceToken($data)
    {
        return DeviceToken::create($data);
    }

    public function getDeviceToken($where)
    {
        return DeviceToken::where($where)->get();
    }
   
    public function updateDeviceToken($id,$data)
    {
        return DeviceToken::where('id',$id)->update($data);
    }
}