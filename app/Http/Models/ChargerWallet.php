<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChargerWallet extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'charger_wallets';
    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'amount',
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
