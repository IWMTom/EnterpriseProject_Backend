<?php 

namespace App\Http\Controllers\API;

use App\Bid;
use App\Http\Controllers\Controller;
use App\Listing;
use App\Rating;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Validator;

class RatingController extends Controller
{

    public function GetRatings($user_id)
    {
        $ratings = Rating::where('reviewed_id', '=', $user_id)->get();

        return response()->json(['success' => $ratings], 200);   
    }

}