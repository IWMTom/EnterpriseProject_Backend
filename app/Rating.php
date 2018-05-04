<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rating extends Model
{
    use SoftDeletes;

    protected $table = 'ratings';

    protected $fillable = [
        'user_id', 'reviewed_id', 'type', 'message'
    ];

    protected $hidden = [
        'deleted_at',
    ];
}