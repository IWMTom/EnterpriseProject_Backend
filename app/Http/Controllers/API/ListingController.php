<?php 

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use App\Listing;
use App\Bid;
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

    public function GetBids($listing_id)
    {
        $listing    = Listing::find($listing_id);
        $bids       = $listing->bids;

        return response()->json(['success' => $bids], 200);
    }

    public function NewBid(Request $request, $listing_id)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'amount'          => 'required'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }

        $listing                    = Listing::find($listing_id);
        $input                      = $request->all();
        $input['user_id']           = Auth::id();
        $input['listing_id']        = $listing_id;
        $bid                        = Bid::create($input);

        $success['id']              = $bid->id;

        return response()->json(['success' => $success], 200);  
    }

    public function DeleteListing($listing_id)
    {
        $listing = Listing::find($listing_id);

        if (Auth::id() == $listing->user_id)
        {
            $listing->delete();
            return response()->json('success', 200);
        }
        else
        {
            return response()->json('error', 200);
        }
    }
}