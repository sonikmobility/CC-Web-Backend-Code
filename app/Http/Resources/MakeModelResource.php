<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Models\User;

class MakeModelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_image' => $this->profile_image,
            'status' => $this->status,
            'provider_name' => $this->provider_name,
            'provider_id' => $this->provider_id,
            'mobile_number' => $this->mobile_number,
            'profile_image_path' => $this->profile_image_path,
            'admin_profile_image_path' => $this->admin_profile_image_path,
            'timezone' => $this->timezone,
            'vehicle_make' => User::find($this->id)->userVehiclesMake,
            'vehicle_model' => User::find($this->id)->userVehiclesModel,
        ];
    }
}
