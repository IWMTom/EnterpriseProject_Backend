<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use SoftDeletes;

    protected $table = 'listings';

    protected $fillable = [
        'user_id', 'item_description', 'item_size', 'important_details', 'collection_location', 'delivery_location', 'collection_city', 'delivery_city', 'distance', 'collection_coord', 'delivery_coord'
    ];

    protected $hidden = ['deleted_at', 'collection_location', 'delivery_location', 'collection_coord', 'delivery_coord', 'bids'];

    protected $appends = ['max_bid', 'min_bid', 'average_bid'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public static function getDistance($from, $to)
    {
	    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.urlencode($from).'&destinations='.urlencode($to).'&key=AIzaSyB8qGi_9Soez-8yzW_2WfxSJeyJKVATlhw';
	 
	    $resp_json = file_get_contents($url);
	    $resp = json_decode($resp_json, true);

	    if ($resp['status'] == 'OK')
	    {       
	    	return Listing::getMilesFromMetres($resp['rows'][0]['elements'][0]['distance']['value']);    
	    }
		else
		{
        	return false;
    	}    	
    }

    public static function getMilesFromMetres($metres)
    {
    	return round(($metres * 0.000621371), 2);
    }

    public static function getCoordFromAddress($resp)
    {
        if ($resp['status'] == 'OK')
        {       
            return $resp['results'][0]['geometry']['location']['lat'].','.$resp['results'][0]['geometry']['location']['lng'];
        }
        else
        {
            return false;
        }
    }

	public static function getCityFromAddress($resp)
	{
	    if ($resp['status'] == 'OK')
	    {       
	    	for ($i = 0; $i < count($resp['results'][0]['address_components']); $i++)
	    	{
	    		if (in_array('postal_town', $resp['results'][0]['address_components'][$i]['types']))
	    		{
	    			return $resp['results'][0]['address_components'][$i]['long_name']; 
	    		}
	    	}       
	    }
		else
		{
        	return false;
    	}
	}

	public function bids()
    {
        return $this->hasMany('App\Bid', 'listing_id', 'id')->orderBy('amount', 'ASC');
    }

    public function contracts()
    {
        return $this->hasMany('App\Contract', 'listing_id', 'id');
    }

    public function getMaxBidAttribute()
    {
    	$amounts = $this->bids->pluck('amount')->toArray();

    	if (empty($amounts))
    	{
    		return 0;
    	}
    	else
    	{
    		return max($amounts);
    	}
    }

    public function getMinBidAttribute()
    {
    	$amounts = $this->bids->pluck('amount')->toArray();

    	if (empty($amounts))
    	{
    		return 0;
    	}
    	else
    	{
    		return min($amounts);
    	}
    }

    public function getAverageBidAttribute()
    {
    	$amounts = $this->bids->pluck('amount')->toArray();

    	if (empty($amounts))
    	{
    		return 0;
    	}
    	else
    	{
    		return round((array_sum($amounts) / count($amounts)), 2);
    	}
    }
}