<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatterySize extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'battery_size';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'vehicle_model_id',
        'charger_type_id',
        'name',
        'mark_as_primary'
    ];

    public function vehicleModel(){
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function chargerType(){
        return $this->belongsTo(ChargerType::class, 'charger_type_id');
    }
}