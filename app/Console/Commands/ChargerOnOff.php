<?php

namespace App\Console\Commands;

use DateTime;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use App\Http\Models\Charger;
use Illuminate\Console\Command;

class ChargerOnOff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charger-on-off:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = CarbonImmutable::now('Asia/Kolkata');
        $get_current_time = $now->toTimeString();
        $all_chargers = Charger::get();
    
        foreach($all_chargers as $charger){
            if(!blank($charger->end_time) && !blank($charger->start_time)){
                $charger_end_time = Carbon::parse($charger->end_time)->toTimeString();
                $charger_start_time = Carbon::parse($charger->start_time)->toTimeString();
                $current = Carbon::createFromTimeString($get_current_time);
                $end = Carbon::createFromTimeString($charger_end_time);
                $start = Carbon::createFromTimeString($charger_start_time);
                $is_between = $current->between($charger_start_time,$charger_end_time);
                if($is_between){
                    if($charger->is_private == 1){
                        $charger->update(['is_private'=>0]);
                    }
                }else{
                    if($charger->is_private == 0){
                        $charger->update(['is_private'=>1]);
                    }
                }
                //\Log::info("charger_diff ".$charger_end_time->diffInMinutes($get_current_time));
            }
        }
    }
}
