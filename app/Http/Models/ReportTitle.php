<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTitle extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'report_titles';

    protected $fillable = [
        'name'
    ];
}
