<?php
namespace App\Http\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Version extends Authenticatable
{
    public $timestamps = false;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

}
