<?php 

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use App\Listing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Validator;

class ListingController extends Controller
{
    public function NewListing(Request $request)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'item_description'          => 'required',
            'item_size'                 => 'required',
            'important_details'         => 'required',
            'collection_location'       => 'required',
            'delivery_location'         => 'required'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }

        $input                      = $request->all();
        $input['user_id']           = Auth::id();
        $input['collection_city']   = Listing::getCityFromAddress($input['collection_location']);
        $input['delivery_city']     = Listing::getCityFromAddress($input['delivery_location']);
        $input['distance']          = Listing::getDistance($input['collection_location'], $input['delivery_location']);
        $listing                    = Listing::create($input);
        
        $success['id']              = $listing->id;

        return response()->json(['success' => $success], 200);        
    }

    public function GetListings()
    {
        $listings = Listing::all();

        return response()->json(['success' => $listings], 200);
    }
}