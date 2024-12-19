<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileStorage extends Model
{
    use HasFactory;

    protected $table = "file_storage";

    public function fileStorageable(){
        return $this->morphsTo();
    }
}
