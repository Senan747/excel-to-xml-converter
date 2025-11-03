<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_filepath',
        'xml_filepath',
        'status',
        'response',
        'uploaded_at'
    ];

    protected array $dates = ['uploaded_at'];


}
