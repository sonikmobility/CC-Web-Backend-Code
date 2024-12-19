<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\Booking;
use App\Http\Models\ChargerWallet;
use App\Http\Models\WalletHistory;
use Carbon\Carbon;
use App\Http\Services\WalletHistoryService;
use DB;

class CancelledBooking extends Command
{
    protected $wallet_history_service = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(WalletHistoryService $wallet_history_service)
    {
        parent::__construct();
        $this->wallet_history_service = $wallet_history_service;
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Kolkata');
        $current_date = Carbon::now('Asia/Kolkata');
        //$current_date->modify('+15 minutes');
        $date = $current_date->format('Y-m-d H:i:s');
        $amount = 0;
        $charge_amount = 0;
        $get_booking = Booking::selectRaw("bookings.*, chargers.user_id as user_id, bookings.user_id as booking_user_id, CASE WHEN bookings.final_charge is null THEN bookings.pre_auth_charge ELSE bookings.final_charge END as refund_charge" )
            ->join('chargers', 'chargers.id', '=', 'bookings.charger_station_id')
            ->leftJoin('charging_history', 'charging_history.booking_id', '=', 'bookings.id')
            ->where(DB::raw('bookings.start_time + INTERVAL 15 Minute'), '<=', $date)
            ->where('bookings.booking_type', 'pre')
            ->where('bookings.is_cancel', 0)
            ->where('charging_history.booking_id', null)
            ->get();
        if(!empty($get_booking)) {
            foreach ($get_booking as $booking_detail) {
                $charger_wallet = ChargerWallet::where('user_id', $booking_detail->user_id)->first();
                if(!blank($charger_wallet)){
                    $charge_amount = $booking_detail->refund_charge*75/100;
                    $amount = $charger_wallet->amount + $charge_amount;
                    $description = "Refund Initiated of Rs.".$charge_amount;
                    \Log::info("charge_amount".$charge_amount);
                    $this->createCreditHistoryForAdmin($charge_amount,$description,$booking_detail->user_id);
                    \Log::info("Cron is working fine!".rand(10,100).'.'.$amount);
                    $update_wallet_amount = $charger_wallet->update(['amount' => $amount]);
                }
                $booking_user_wallet = ChargerWallet::where('user_id', $booking_detail->booking_user_id)->first();
                if(!blank($booking_user_wallet)){
                    $b_charge_amount = $booking_detail->refund_charge*25/100;
                    $b_amount = $booking_user_wallet->amount + $b_charge_amount;
                    $description = "Refund Initiated of Rs.".$b_charge_amount;
                    \Log::info("b_charge_amount".$b_charge_amount);
                    $this->createCreditHistoryForAdmin($b_charge_amount,$description,$booking_detail->booking_user_id);
                    \Log::info("Cron is working fine!".rand(10,100).'.'.$b_amount);
                    $update_wallet_amount = $booking_user_wallet->update(['amount' => $b_amount]);
                }
            }
            $booking = array_column($get_booking->toArray(), 'id');
            Booking::whereIn('id', $booking)->update(array('is_cancel' => 1, 'cancellation_reason' => 2, 'cancelled_time' => $now, 'refund_amount' => $charge_amount, 'refund_percentage' => 25));
        }
    }

    public function createCreditHistoryForAdmin($amount,$description,$user_id){
        \Log::info("amount".$amount);
        \Log::info("description".$description);
        \Log::info("user_id".$user_id);
        $creation_data = [
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => 'credit',
            'description' => $description,
        ];
        return WalletHistory::create($creation_data);
    }
}
