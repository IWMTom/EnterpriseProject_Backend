<?php

namespace App;

use App\User;
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
		$user = User::find($this->user_id);

		return $user->known_as;
    }

    public function listing()
    {
        return $this->belongsTo('App\Listing');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}