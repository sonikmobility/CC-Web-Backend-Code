<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReport extends Model
{
    use HasFactory;
    protected $table = 'user_reports';

    protected $fillable = [
        'user_id',
        'report_reason',
        'description',
    ];
    
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
