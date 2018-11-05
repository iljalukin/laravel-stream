<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    protected $fillable = ['payload','processed'];

    protected $guarded = [];

    public $attributes = [];

    protected $casts = ['payload' => 'json'];
}
