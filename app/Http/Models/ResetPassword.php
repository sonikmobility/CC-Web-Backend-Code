<?php

namespace App\Http\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ResetPassword extends Authenticatable
{
 	public $timestamps = false;
  	protected $table = 'reset_password';

	protected $guarded = [];
	 
}