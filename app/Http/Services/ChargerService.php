<?php

namespace App\Http\Services;

use App\Http\Models\Charger;
use Carbon\Carbon;

class ChargerService
{
    public function checkChargerExist($data)
    {
        $existing_charger = Charger::where('is_private', $data['is_private'])->where('user_id', $data['user_id'])->first();
        if (!blank($existing_charger)) {
            return $existing_charger;
        }
    }

    public function storeCharger($data)
    {
        if (isset($data["start_time"])) {
            $start = carbon::parse($data["start_time"]);
            $data['start_time'] = $start;
        }
        if (isset($data["end_time"])) {
            $end =  carbon::parse($data["end_time"]);
            $data['end_time'] = $end;
        }
        return Charger::create($data);
    }

    // Get Charger By Id
    public function getCharger($where)
    {
        return Charger::where($where)->get();
    }

    // 
    public function getChargerNearByAddress($latitude, $longitude)
    { 
        if (!blank($latitude) && $longitude) {
            $chargers = Charger::selectRaw("chargers.*,
                chargers.connector_type as charger_pin_type,
                chargers.charge_type as charging_type,
                111.111 *
                DEGREES(ACOS(LEAST(1.0, COS(RADIANS(latitude))
                 * COS(RADIANS({$latitude}))
                 * COS(RADIANS(longitude - {$longitude}))
                 + SIN(RADIANS(latitude))
                 * SIN(RADIANS({$latitude}))))) as distanceValue")
                ->leftJoin('charger_details', 'chargers.id', '=', 'charger_details.charger_id')
                ->where('is_private', 0)
                ->whereNotNull('price')
                ->whereHas('users',function($q){
                    $q->whereNull('deleted_at');
                })
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->orderBy('distanceValue', 'ASC')
                ->get();
            foreach ($chargers as $key => $charger) {
                $chargers[$key]->distance = number_format($charger->distanceValue, 1) . ' km';
                $chargers[$key]->start_time = date('h:i a', strtotime($charger->start_time));
                $chargers[$key]->end_time = date('h:i a', strtotime($charger->end_time));
            }
            return $chargers->sortBy('distanceValue')->values();
        }
        return Charger::selectRaw("*, '-' as distance")->orderBy('distance', 'ASC')
            ->get();
    }

    public function updateCharger($id, $data)
    {
        if (isset($data["start_time"])) {

            $start = carbon::parse($data["start_time"]);
            $data['start_time'] = $start;
        }
        if (isset($data["end_time"])) {
            $end =  carbon::parse($data["end_time"]);
            $data['end_time'] = $end;
        }
        $update = Charger::where('id', $id)->update($data);
        $charger = Charger::where('id', $id)->first();
        return $charger;
    }

    public function deleteCharger($id)
    {
        $charger = Charger::where('id', $id)->delete();
        return $charger;
    }
}
