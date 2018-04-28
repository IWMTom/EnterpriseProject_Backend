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
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Notification;

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
        $input                      = $request->all();
        $input['amount']            = ($input['amount'] / 100);
        $input['user_id']           = Auth::id();
        $input['listing_id']        = $listing_id;
        $bid                        = Bid::create($input);

        $success['id']              = $bid->id;

        $this->SendPushNotification($listing, $bid);

        return response()->json(['success' => $success], 200);  
    }

    public function DeleteListing($listing_id)
    {
        $listing = Listing::find($listing_id);

        if (Auth::id() == $listing->user_id)
        {
            $listing->delete();
            $listing->bids()->delete();
            return response()->json('success', 200);
        }
        else
        {
            return response()->json('error', 200);
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

        $response = $client->send($message);
    }
}