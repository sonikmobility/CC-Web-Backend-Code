<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVehicle extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'user_vehicle';

    protected $fillable = [
        'vehicle_make_id',
        'vehicle_model_id',
        'user_id',
        'registration_number',
        'battery_size',
        'charger_type',
        'mark_as_primary'
    ];

    public function vehicleMakes()
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id');
    }
    public function vehicleModels()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }
    public function chargerType()
    {
        return $this->belongsTo(ChargerType::class, 'charger_type');
    }
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
