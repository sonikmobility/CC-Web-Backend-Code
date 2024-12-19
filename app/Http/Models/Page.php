<?php

namespace App\Http\Models;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    public $timestamps = false;
    protected $table = 'static_pages';

    protected $guarded = [];
    
}