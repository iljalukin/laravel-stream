<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $guarded = [];

    protected $fillable = [
       'uid', 'title', 'original_name', 'disk', 'path', 'stream_path', 'processed', 'target', 'converted_at'
    ];

    public $attributes = [];

    protected $casts = ['target' => 'json'];
}
