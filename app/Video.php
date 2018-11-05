<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $dates = [
        'converted_for_streaming_at',
    ];

    protected $guarded = [];

    protected $fillable = [
       'title','original_name','disk', 'path', 'stream_path', 'processed', 'target'
    ];

    public $attributes = [];

    protected $casts = ['target' => 'json'];
}
