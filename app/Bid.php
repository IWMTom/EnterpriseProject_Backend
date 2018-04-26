<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bid extends Model
{
    use SoftDeletes;

    protected $table = 'bids';

    protected $fillable = [
        'user_id', 'listing_id', 'amount', 'message',
    ];

    protected $hidden = ['deleted_at'];

    protected $appends = ['username'];


    public function getUsernameAttribute()
    {
    	return "Big Boy";
    }

    public function listing()
    {
        return $this->belongsTo('App\Listing');
    }
}