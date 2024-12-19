<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChargerType extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'charger_type';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'vehicle_model_id',
        'type',
        'mark_as_primary'
    ];

    public function vehicleModel(){
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function batterySize(){
        return $this->hasOne(BatterySize::class,'charger_type_id');
    }
}
