<?php

namespace App\Http\Controllers;

use App\Http\Models\Role;
use App\Http\Models\User;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Http\Models\User  $user
     * @return \App\Http\Models\User  $user
     */
    public function index(User $user)
    {
        return $user->load('roles');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Http\Models\User  $user
     * @return \App\Http\Models\User  $user
     */
    public function store(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => 'required|integer',
        ]);
        $role = Role::find($data['role_id']);
        if (!$user->roles()->find($data['role_id'])) {
            $user->roles()->attach($role);
        }
        return $user->load('roles');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Models\User  $user
     * @param  \App\Http\Models\Role  $role
     * @return \App\Http\Models\User  $user
     */
    public function destroy(User $user, Role $role)
    {
        $user->roles()->detach($role);
        return $user->load('roles');
    }
}
