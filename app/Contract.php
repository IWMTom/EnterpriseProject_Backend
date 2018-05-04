<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use SoftDeletes;

    protected $table = 'contracts';

    protected $fillable = [
        'bid_id', 'listing_id',
    ];

    protected $hidden = ['deleted_at'];

    public function bid()
    {
        return $this->belongsTo('App\Bid');
    }

    public function listing()
    {
        return $this->belongsTo('App\Listing');
    }
}