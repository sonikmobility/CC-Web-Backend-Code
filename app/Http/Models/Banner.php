<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $appends = ['banner_image_path','web_image_path'];

    public $timestamps = true;
    protected $table = 'banners';
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'image',
        'status'
    ];

    public function getWebImagePathAttribute()
    {
        return (config('constant.storage_path') . 'Banner/');
    }

    public function getBannerImagePathAttribute()
    {
        return (config('constant.storage_path') . 'Banner/original/');
    }
}
