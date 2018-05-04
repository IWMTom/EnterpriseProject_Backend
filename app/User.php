<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name', 'known_as', 'dob', 'postcode', 'email', 'password', 'reg_ip', 'profile_photo', 'phone_number'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'reg_ip', 'deleted_at',
    ];

    protected $dates = ['dob'];

    protected $appends = ['city'];

    public function listings()
    {
        return $this->hasMany('App\Listing', 'user_id', 'id')->where('active', '=', '1');
    }

    public function listings_inactive()
    {
        return $this->hasMany('App\Listing', 'user_id', 'id')->where('active', '=', '0');
    }

    public function remaining_jobs()
    {
        return $this->hasManyThrough('App\Contract', 'App\Bid');
    }

    public function remaining_shipments()
    {
        return $this->hasManyThrough('App\Contract', 'App\Listing');
    }

    public function ratings()
    {
        return $this->hasMany('App\Rating', 'reviewed_id', 'id');
    }

    public function contracts()
    {
        return $this->hasMany('App\Contract', 'user_id', 'id');
    }

    public function getCityAttribute()
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($this->postcode).'&key=AIzaSyB8qGi_9Soez-8yzW_2WfxSJeyJKVATlhw';
        $json = json_decode(file_get_contents($url), true);

        return Listing::getCityFromAddress($json);
    }

    public function GetCoordFromPostcode()
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($this->postcode).'&key=AIzaSyB8qGi_9Soez-8yzW_2WfxSJeyJKVATlhw';
        $json = json_decode(file_get_contents($url), true);

        if ($json['status'] == 'OK')
        {       
            return $json['results'][0]['geometry']['location']['lat'].','.$json['results'][0]['geometry']['location']['lng'];
        }
        else
        {
            return false;
        }
    }
}
