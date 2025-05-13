<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Models\VehicleMake;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleModel extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'vehicle_model';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'vehicle_make_id'
    ];

    public function userVehicleMakes(){
        return $this->belongsTo(VehicleMake::class,'vehicle_make_id');
    }

    public function chargerType(){
        return $this->hasMany(ChargerType::class,'vehicle_model_id');
    }

    public function batterySize(){
        return $this->hasMany(BatterySize::class,'vehicle_model_id');
    }
}
