<?php

namespace App\Http\Services;

use App\Http\Models\ChargingHistory;
use App\Http\Models\Booking;
use Carbon\Carbon;
use App\Http\Services\ChargerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargingHistoryService
{
	public function __construct(ChargerService $charger_service)
	{
		$this->charger_service = $charger_service;
	}

	public function storeChargingHistory($data)
	{
		// Update Charger status (Available to Busy)
		$charger_status_data = [
			'status' => '2',
		];
		$this->charger_service->updateCharger($data['charger_station_id'], $charger_status_data);
	

		DB::beginTransaction();
		try {
			$check_history = ChargingHistory::where('booking_id',$data['booking_id'])->where('charger_station_id',$data['charger_station_id'])->first();
			
			if(!$check_history){
				$charging_history = ChargingHistory::create($data);
				DB::commit();
				
				return $charging_history;
			}

			return response()->json([
				'code' => config('constant.UNSUCCESS'),
				'msg' => 'Failed to store charging history.',
			]);
		} catch (\Exception $e) {
			DB::rollBack();			
			Log::error('Failed to store charging history: '. $data['booking_id'] . ' ' . $e->getMessage());
	
			return response()->json([
				'code' => config('constant.UNSUCCESS'),
				'msg' => 'Failed to store charging history.',
			]);
		}
	}
	

	public function getChargingHistory($where)
	{
		return ChargingHistory::where($where)->get();
	}

	public function updateChargingHistory($id, $data)
	{
		$history_data = ChargingHistory::where('id', $id)->with('bookings')->first();
		if (!blank($history_data)) {
			// Update Charger status (Busy to Available)
			$charger_status_data = [
				'status' => '0',
			];
			$this->charger_service->updateCharger($history_data->charger_station_id, $charger_status_data);
			$from = Carbon::parse($history_data->charging_start_time);
            $to = $data['charging_end_time'];
            $minutes = $to->diffInMinutes($from);
			$update_booking = Booking::where('id',$history_data->booking_id)->update(['minutes'=>$minutes,'end_time' => $to]);
			$history_data->update($data);
		}
		return $history_data;
	}

	public function checkBookingTime($booking_id,$charging_start_time){
		return Booking::where('id',$booking_id)->whereRaw("DATE_FORMAT(start_time,'%Y-%m-%d %H:%i:%s') <= '$charging_start_time'")->first();
	}

	public function myChargingHistory($user_id, $longitude, $latitude)
	{
		$history = ChargingHistory::selectRaw("charging_history.*, chargers.name as charger_station_name, chargers.address as address, DATE_FORMAT(charging_history.charging_start_time, '%l:%i %p, %d %b,%Y') as charging_start_time, DATE_FORMAT(charging_history.charging_end_time, '%l:%i %p, %d %b,%Y') as charging_end_time, DATE_FORMAT(chargers.start_time, '%l:%i %p') as charger_start_time, DATE_FORMAT(chargers.end_time, '%l:%i %p') as charger_end_time, chargers.latitude as latitude, chargers.longitude as longitude ,CONCAT(ROUND(111.111 *
                DEGREES(ACOS(LEAST(1.0, COS(RADIANS(chargers.latitude))
                 * COS(RADIANS({$latitude}))
                 * COS(RADIANS(chargers.longitude - {$longitude}))
                 + SIN(RADIANS(chargers.latitude))
                 * SIN(RADIANS({$latitude}))))),2),' km') as distance")
			->join('chargers', 'chargers.id', '=', 'charging_history.charger_station_id')
			->join('bookings', 'bookings.id', '=', 'charging_history.booking_id')
			->where('charging_history.user_id', $user_id)
			->whereNotNull('bookings.pre_auth_charge')
			->whereNotNull('bookings.pre_auth_transaction_id')
			->whereNotNull('bookings.final_charge')
			->whereNotNull('bookings.final_transaction_id')
			->get();
		return $history;
	}
}
