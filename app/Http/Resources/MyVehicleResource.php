<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

use App\Http\Models\VehicleMake;
use App\Http\Models\VehicleModel;

class MyVehicleResource extends ResourceCollection
{
  /**
   * Transform the resource collection into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    return [
      "id" => isset($this->collection['id']) ? $this->collection['id'] : [],
      "vehicle_make_id" => isset($this->collection['vehicle_make_id']) ? $this->collection['vehicle_make_id'] : [],
      "vehicle_model_id" => isset($this->collection['vehicle_model_id']) ? $this->collection['vehicle_model_id'] : [],
      "vehicle_make_name" => isset($this->collection['vehicle_make_id']) ? VehicleMake::find($this->collection['vehicle_make_id'])->name : [],
      "vehicle_model_name" => isset($this['vehicle_model_id']) ? VehicleModel::find($this->collection['vehicle_model_id'])->name : [],
      "registration_number" => isset($this->collection['registration_number']) ? $this->collection['registration_number'] : [],
    ];
  }
}
