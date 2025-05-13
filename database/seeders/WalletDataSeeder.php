<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Http\Models\User;
use App\Http\Models\ChargerWallet;

class WalletDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $get_user = User::get();
        foreach($get_user as $user){
            $data = [
                'user_id' => $user->id,
                'amount' => 0
            ];
            ChargerWallet::create($data);
        }
    }
}
