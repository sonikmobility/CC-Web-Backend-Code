<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMake extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'vehicle_make';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name'
    ];

    public function userVehicles(){
        return $this->belongsToMany(User::class,'user_vehicle');
    }

    public function vehicleModels(){
        return $this->hasMany(VehicleModel::class,'vehicle_make_id');
    }
}
