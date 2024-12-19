<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletHistory extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = 'wallet_histories';

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'transaction_id',
        'source',
        'description'
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
