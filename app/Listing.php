<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use SoftDeletes;

    protected $table = 'listings';

    protected $fillable = [
        'user_id', 'item_description', 'item_size', 'important_details', 'collection_location', 'delivery_location', 'collection_city', 'delivery_city', 'distance',
    ];

    protected $hidden = ['deleted_at', 'collection_location', 'delivery_location'];


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

	public static function getCityFromAddress($address)
	{
	    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&key=AIzaSyB8qGi_9Soez-8yzW_2WfxSJeyJKVATlhw';
	 
	    $resp_json = file_get_contents($url);
	    $resp = json_decode($resp_json, true);

	    if ($resp['status'] == 'OK')
	    {       
	    	for ($i = 0; $i < count($resp['results'][0]['address_components']); $i++)
	    	{
	    		if (in_array('postal_town', $resp['results'][0]['address_components'][$i]['types']))
	    		{
	    			return $resp['results'][0]['address_components'][2]['long_name']; 
	    		}
	    	}       
	    }
		else
		{
        	return false;
    	}
	}
}