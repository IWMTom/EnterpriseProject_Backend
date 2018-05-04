<?php 

namespace App\Http\Controllers\API;

use App\Bid;
use App\Http\Controllers\Controller;
use App\Listing;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Validator;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;

class ListingController extends Controller
{
    public function NewListing(Request $request)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'item_description'          => 'required',
            'item_size'                 => 'required',
            'collection_location'       => 'required',
            'delivery_location'         => 'required'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }

        $input                      = $request->all();
        $input['user_id']           = Auth::id();

        $collection_url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($input['collection_location']).'&key=AIzaSyB8qGi_9Soez-8yzW_2WfxSJeyJKVATlhw';
        $collection_json = json_decode(file_get_contents($collection_url), true);

        $delivery_url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($input['delivery_location']).'&key=AIzaSyB8qGi_9Soez-8yzW_2WfxSJeyJKVATlhw';
        $delivery_json = json_decode(file_get_contents($delivery_url), true);

        $input['collection_city']   = Listing::getCityFromAddress($collection_json);
        $input['delivery_city']     = Listing::getCityFromAddress($delivery_json);
        $input['collection_coord']  = Listing::getCoordFromAddress($collection_json);
        $input['delivery_coord']    = Listing::getCoordFromAddress($delivery_json);
        $input['distance']          = Listing::getDistance($input['collection_location'], $input['delivery_location']);
        $listing                    = Listing::create($input);
        
        $success['id']              = $listing->id;

        return response()->json(['success' => $success], 200);        
    }

    public function GetListings()
    {
        $listings = Listing::where('user_id', '!=', Auth::id())->where('active', '=', '1')->get();

        return response()->json(['success' => $listings], 200);
    }

    public function GetListingsByRadius($radius)
    {
        $longlat = explode(',', Auth::user()->postcode_coord);

        $listings = Listing::select(DB::raw("*, ( 3959 * acos( cos( radians(substring_index(collection_coord, ',', 1)) ) * cos( radians(".$longlat[0].") ) * cos( radians(".$longlat[1].") - radians(substring_index(collection_coord, ',', -1)) ) + sin( radians(substring_index(collection_coord, ',', 1)) ) * sin( radians(".$longlat[0].")))) AS collection_radius, ( 3959 * acos( cos( radians(substring_index(delivery_coord, ',', 1)) ) * cos( radians(".$longlat[0].") ) * cos( radians(".$longlat[1].") - radians(substring_index(delivery_coord, ',', -1)) ) + sin( radians(substring_index(delivery_coord, ',', 1)) ) * sin( radians(".$longlat[0].")))) AS delivery_radius"))
            ->having('collection_radius', '<', $radius)
            ->having('delivery_radius', '<', $radius)
            ->where('active', '=', '1')
            ->get();

        return response()->json(['success' => $listings], 200);
    }

    public function GetListingsByUser()
    {
        $listings = Auth::user()->listings;

        return response()->json(['success' => $listings], 200);
    }

    public function FindListing($id)
    {
        $listing = Listing::find($id);

        return response()->json(['success' => array($listing)], 200);
    }

    public function GetBids($listing_id)
    {
        $listing    = Listing::find($listing_id);
        $bids       = $listing->bids;

        return response()->json(['success' => $bids], 200);
    }

    public function NewBid(Request $request, $listing_id)
    {
        $messages = array(
            'min'             => 'The amount must be at least £0.01',
        );

        $validator = Validator::make($request->all(),
        [
            'amount'          => 'required|numeric|min:1'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }

        $listing                    = Listing::find($listing_id);

        if ($listing->user_id == Auth::id())
        {
            return response()->json(['error' => "You can't bid on your own listing!"], 200);            
        }

        if ($existing_bid = Bid::where('user_id', '=', Auth::id())->where('listing_id', '=', $listing_id)->first())
        {
            $existing_bid->amount = ($request->amount / 100);
            $existing_bid->message = $request->message;
            $existing_bid->save();

            $success['id'] = $existing_bid->id;

            return response()->json(['success' => $success], 200);  
        }
        else
        {
            $input                      = $request->all();
            $input['amount']            = ($input['amount'] / 100);
            $input['user_id']           = Auth::id();
            $input['listing_id']        = $listing_id;
            $bid                        = Bid::create($input);

            $success['id']              = $bid->id;

            $this->SendPushNotification($listing, $bid);

            return response()->json(['success' => $success], 200);  
        }
    }

    public function DeleteListing($listing_id)
    {
        $listing = Listing::find($listing_id);

        if (Auth::id() == $listing->user_id)
        {
            $listing->delete();
            $listing->bids()->delete();
            $listing->contracts()->delete();
            return response()->json(['success' => array()], 200);
        }
        else
        {
            return response()->json(['error' => ""], 200);
        }
    }

    public function SendPushNotification($listing, $bid)
    {
        $server_key = 'AAAARdhcX_k:APA91bFMOSApsSg2ph_D7cWAUXj_QOdE8vkiYwucjl60xIVpjA-BH9mGdDoxqAQDABHNYek93VOj8KXFuTwz-5m9kw06kiDkA7EkNoyHWyXdmS161QPbuW1ap8QAOTeK-04lcrYll8H5';
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $message->setPriority('high');
        $message->addRecipient(new Device(User::find($listing->user_id)->push_token));
        $message
            ->setNotification(new Notification($bid->username.' has bid on your listing', 'Bid amount: £'.number_format($bid->amount, 2)));
        $message->setData(['notification_bid' => $listing->id]);

        $response = $client->send($message);
    }

    public function AcceptBid($id)
    {
        $bid = Bid::find($id);
        $listing = $bid->listing;

        if (Auth::id() == $bid->listing->user_id)
        {
            if ($listing->active == 1)
            {
                $contract = new Contract();
                $contract->bid_id = $bid->id;
                $contract->listing_id = $bid->listing->id;
                $contract->save();

                $listing = $bid->listing;
                $listing->active = 0;
                $listing->save();

                $success['id'] = $contract->id;

                return response()->json(['success' => $success], 200);
            }
            else
            {
                return response()->json(['error' => "A bid has already been accepted for this listing"], 200);
            }
        }
        else
        {
            return response()->json(['error' => "Oi, this isn't your listing!"], 200);
        }
    }

    public function DeleteBid($id)
    {
        $bid = Bid::find($id);

        if (Auth::id() == $bid->listing->user_id)
        {
            $bid->delete();

            return response()->json(['success' => array()], 200);
        }
        else
        {
            return response()->json(['error' => "Oi, this isn't your bid!"], 200);
        }
    }

    public function BidDetails($id)
    {
        $bid = Bid::find($id);
        $listing = $bid->listing;
        
        $success['amount']              = $bid->amount;
        $success['message']             = $bid->message;
        $success['item_description']    = $listing->item_description;
        $success['name']                = $bid->user->known_as;
        $success['reputation']          = $bid->user->reputation;
        $success['city']                = $bid->user->city;

        return response()->json(['success' => $success], 200);
    }

    public function GetContractsCourier()
    {
        $contracts      = Auth::user()->remaining_jobs;
        $return_data    = array();

        foreach ($contracts as $contract)
        {
            $data = array();
            $data['listing_id']         = $contract->listing_id;
            $data['bid_id']             = $contract->bid_id;
            $data['item_description']   = $contract->listing->item_description;
            $data['bid_amount']         = $contract->bid->amount;
            $data['bid_message']        = $contract->bid->message;
            $data['courier_id']         = $contract->bid->user_id;
            $data['courier_alias']      = $contract->bid->user->known_as;
            $data['shipper_id']         = $contract->listing->user_id;
            $data['sender_alias']       = $contract->listing->user->known_as;
            $data['collected']          = $contract->collected;
            $data['delivered']          = $contract->delivered;
            $data['confirmed']          = $contract->confirmed;

            array_push($return_data, $data);
        }

        return response()->json(['success' => $return_data], 200);
    }

    public function GetContractsShipper()
    {
        $contracts      = Auth::user()->remaining_shipments;
        $return_data    = array();

        foreach ($contracts as $contract)
        {
            $data = array();
            $data['listing_id']         = $contract->listing_id;
            $data['bid_id']             = $contract->bid_id;
            $data['item_description']   = $contract->listing->item_description;
            $data['bid_amount']         = $contract->bid->amount;
            $data['bid_message']        = $contract->bid->message;
            $data['courier_id']         = $contract->bid->user_id;
            $data['courier_alias']      = $contract->bid->user->known_as;
            $data['shipper_id']         = $contract->listing->user_id;
            $data['sender_alias']       = $contract->listing->user->known_as;
            $data['collected']          = $contract->collected;
            $data['delivered']          = $contract->delivered;
            $data['confirmed']          = $contract->confirmed;

            array_push($return_data, $data);
        }

        return response()->json(['success' => $return_data], 200);
    }

}