<?php

namespace App\Http\Services;
use App\Http\Models\ResetPassword;

class ResetPasswordService
{
    public function delete($where)
    {
        return ResetPassword::where($where)->delete();
    }

    public function getToken($where)
    {
        return ResetPassword::where($where);
    }

    public function store($data)
    {
        return ResetPassword::create($data);
    }
}