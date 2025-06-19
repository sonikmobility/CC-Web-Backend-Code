<?php

namespace App\Http\Services;

use App\Http\Models\User;
use App\Http\Models\Page;
use App\Http\Models\Version;
use App\Http\Models\Charger;
use App\Http\Models\VehicleMake;
use App\Http\Models\VehicleModel;
use App\Http\Models\ChargerType;
use App\Http\Models\BatterySize;
use App\Http\Models\WalletHistory;
use App\Http\Services\DateTimeZone;
use App\Http\Services\DateTime;
class ExportService
{
    public function usersExport()
    {
        $data = User::select('id', 'first_name', 'last_name', 'email', 'mobile_number')->get();
        if (!blank($data)) {
            // if use get() method then use each (if use each then not required to use foreach)
            // if use all() method then use only setAppends([])
            $data->each->setAppends([]);
        }
        $header = ['ID', 'First Name', 'Last Name', 'Email', 'Mobile Number'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function contentPagesExport()
    {
        $data = Page::select('id', 'page_name', 'page_title')->get();
        $header = ['ID', 'Page Name', 'Page Title'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function versionsExport()
    {
        $data = Version::select('id', 'ios_version', 'android_version')->get();
        $header = ['ID', 'IOS Version', 'Android Version'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function chargersExport($charger_type)
    {
        if ($charger_type == "sonik") {
            $data = Charger::selectRaw('chargers.id,chargers.uuid,chargers.name,chargers.address,chargers.city,chargers.zip_code,chargers.price, users.first_name as first_name, users.last_name as last_name')
                ->join('users', 'users.id', '=', 'chargers.user_id')
                ->where('chargers.is_private', 0)->where('user_id', 1)->get();
        } else {
            $charger_type = ($charger_type == "public") ? 0 : 1;
            $data = Charger::selectRaw('chargers.id,chargers.uuid,chargers.name,chargers.address,chargers.city,chargers.zip_code,chargers.price, users.first_name as first_name, users.last_name as last_name')
                ->join('users', 'users.id', '=', 'chargers.user_id')
                ->where('chargers.is_private', $charger_type)->get();
        }

        if (!blank($data)) {
            $data->each->setAppends([]);
        }
        $header = ['ID', 'UUID', 'Charger Station Name', 'Address', 'City', 'Zip Code', 'Price', 'First Name', 'Last Name'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function chargerStatusExport($charger_status)
    {
        $data = Charger::selectRaw('chargers.id,chargers.uuid,chargers.name,chargers.address,chargers.city,chargers.zip_code,chargers.price, users.first_name as first_name, users.last_name as last_name')
            ->join('users', 'users.id', '=', 'chargers.user_id')
            ->where('chargers.status', $charger_status)->get();
        if (!blank($data)) {
            $data->each->setAppends([]);
        }
        $header = ['ID', 'UUID', 'Charger Station Name', 'Address', 'City', 'Zip Code', 'Price', 'First Name', 'Last Name'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function vehicleMakeExport()
    {
        $data = VehicleMake::select('id', 'name')->get();
        $header = ['ID', 'Name'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function vehicleModelExport()
    {
        $data = VehicleModel::selectRaw('vehicle_model.id,vehicle_model.name,vehicle_make.name as vehicle_make_name')
            ->join('vehicle_make', 'vehicle_make.id', '=', 'vehicle_model.vehicle_make_id')
            ->get();
        $header = ['ID', 'Model Name', 'Make Name'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function chargerTypeExport()
    {
        $data = ChargerType::select('id', 'type')->get();
        $header = ['ID', 'Type'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function batterySizeExport()
    {
        $data = BatterySize::select('id', 'name')->get();
        $header = ['ID', 'Name'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];
        return $return_data;
    }

    public function userTransactionsExport($user_id)
    {
        // Fetch data from the database
        $data = WalletHistory::selectRaw('wallet_histories.user_id, wallet_histories.transaction_id, wallet_histories.payment_history_id, wallet_histories.amount, wallet_histories.description, wallet_histories.created_at')
            ->where('wallet_histories.user_id', $user_id)
            ->get();



        foreach ($data as $record) {
            // Ensure created_at and updated_at are DateTime objects
            $created_at = new \DateTime($record->created_at);


            // Separate created_at date and time
            $record->created_date = $created_at->format('Y-m-d'); // Adjust the format as needed
            $record->created_time = $created_at->format('H:i:s'); // Adjust the format as needed


            // Optionally unset the original created_at and updated_at if not needed
            unset($record->created_at);

        }

        $header = ['User ID', 'Transaction ID', 'Payment History ID', 'Amount', 'Description', 'Created Date', 'Created Time'];
        $return_data = [
            'data' => $data,
            'header' => $header
        ];

        return $return_data;
    }

    public function allUserTransactionsExport($startDate = null, $endDate = null)
    {
        $query = WalletHistory::selectRaw('wallet_histories.id,
                      wallet_histories.user_id, 
                      wallet_histories.transaction_id, 
                      wallet_histories.payment_history_id, 
                      wallet_histories.amount, 
                      wallet_histories.description, 
                      wallet_histories.created_at, 
                      wallet_histories.type,
                      wallet_histories.source,
                      users.first_name,
                      users.last_name')
            ->join('users', 'wallet_histories.user_id', '=', 'users.id');

        if ($startDate && $endDate) {
            $query->whereBetween('wallet_histories.created_at', [$startDate, $endDate]);
        }

        $data = $query->orderBy('wallet_histories.created_at', 'asc')->get();

        foreach ($data as $record) {
            $created_at = new \DateTime($record->created_at, new \DateTimeZone('UTC'));
            $created_at->setTimezone(new \DateTimeZone('Asia/Kolkata'));

            $record->created_date = $created_at->format('Y-m-d');
            $record->created_time = $created_at->format('H:i:s');
            $record->user_name = $record->first_name . ' ' . $record->last_name;
            unset($record->created_at);
        }

        $data = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'user_name' => $item->user_name,
                'user_id' => $item->user_id,
                'amount' => $item->amount,
                'transaction_id' => $item->transaction_id,
                'payment_history_id' => $item->payment_history_id,
                'type' => $item->type,
                'source' => $item->source,
                'description' => $item->description,
                'created_date' => $item->created_date,
                'created_time' => $item->created_time,
            ];
        });

        $header = ['Id', 'User Name', 'User ID', 'Amount', 'Transaction ID', 'Payment History ID', 'Type', 'Source', 'Description', 'Created Date', 'Created Time'];

        return [
            'data' => $data,
            'header' => $header,
        ];
    }


}
