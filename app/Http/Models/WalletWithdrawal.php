<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Models\User;
use Carbon\Carbon;

class WalletWithdrawal extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    protected $table = 'wallet_withdrawal_request';
    protected $guarded = [];
    protected $date = ['created_at','updated_at'];

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'ifsc_code',
        'account_holder_name',
        'status' // 0 - Pending, 1 - Approve, 2 - Decline
    ];
    
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
