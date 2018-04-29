<?php 

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Validator;

class UserController extends Controller
{
    public function DoRegister(Request $request)
    {
        $eighteenYearsAgo = Carbon::now()->subYears(18)->addDay(1)->format('Y-m-d');
        
        $messages = array(
            'before'                        => 'You must be aged 18 or over!',
            'full_name.required'            => 'You must enter your full name!',
            'known_as.required'             => 'You must enter what you want to be known as!',
            'dob.required'                  => 'You must enter your date of birth!',
            'dob.date'                      => 'Your date of birth must be in date format!',
            'postcode.required'             => 'You must enter your postcode!',
            'postcode.regex'                => 'Your postcode must be in the correct format!',
            'phone_number.regex'            => 'Your phone number must be in the correct format!',
            'email.required'                => 'You must enter your email address!',
            'email.email'                   => 'Your email address must be in the correct format!',
            'email.users'                   => 'Another user has already registered with that email address!',
            'password.required'             => 'You must enter a password!',
            'confirm_password.required'     => 'You must enter the same password in both fields!',
            'confirm_password.same'         => 'You must enter the same password in both fields!',
            'profile_photo.size'            => 'Your profile photo cannot be larger than 1GB!'
        );

        $validator = Validator::make($request->all(),
        [
            'full_name'         => 'required',
            'known_as'          => 'required',
            'dob'               => 'required|date|before:'.$eighteenYearsAgo,
            'postcode'          => array('required', 'regex:/^[a-zA-Z]{1,2}([0-9]{1,2}|[0-9][a-zA-Z])\s*[0-9][a-zA-Z]{2}$/'),
            'phone_number'      => array('required', 'regex:/^(\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$/'),
            'email'             => 'required|email|unique:users',
            'password'          => 'required',
            'confirm_password'  => 'required|same:password'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }

        $input                      = $request->all();
        $input['password']          = Hash::make($input['password']);
        $input['reg_ip']            = $request->ip();
        $input['dob']               = Carbon::createFromFormat('d-m-Y', $input['dob']);

        if (isset($input['profile_photo']))
        {
            $input['profile_photo'] = base64_encode(Image::make($input['profile_photo'])->fit(500, 500, function ($constraint)
                                                                                        {
                                                                                            $constraint->upsize();
                                                                                        })->encode()->encoded);
        }

        $user                       = User::create($input);
        $success['token']           = $user->createToken('MyApp')->accessToken;

        return response()->json(['success' => $success], 200);
    }

    public function DoLogin()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')]))
        {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            return response()->json(['success' => $success], 200);
        }
        else
        {
            return response()->json(['error' => 'Email address or password invalid'], 200);
        }
    }

    public function GetDetails()
    {
        $user = Auth::user();
        return response()->json(['success' => $user], 200);
    }

    public function GetProfilePhoto($id)
    {
        $user       = User::find($id);
        $img        = Image::make($user->profile_photo);

        return $img->response('jpg');
    }

    public function UpdatePushToken(Request $request)
    {
        $user = Auth::user();
        $user->push_token = $request->push_token;
        $user->save();

        return response()->json(['success' => array()], 200);
    }

    public function UpdatePassword(Request $request)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'current_password'          => 'required',
            'new_password'              => 'required'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }
        else if (!Hash::check($request->current_password, Auth::user()->password))
        {
            return response()->json(['error' => "Invalid password!"], 200);
        }
        else
        {
            $user = Auth::user();
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['success' => ""], 200);
        }
    }

    public function UpdateEmail(Request $request)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'email'              => 'required|email|unique:users,email'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }
        else
        {
            $user = Auth::user();
            $user->email = $request->email;
            $user->save();

            return response()->json(['success' => ""], 200);
        }        
    }

    public function UpdatePhoneNumber(Request $request)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'phone_number'              => array('required', 'regex:/^(\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$/')
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }
        else
        {
            $user = Auth::user();
            $user->phone_number = $request->phone_number;
            $user->save();

            return response()->json(['success' => ""], 200);
        }         
    }

    public function UpdateUserInfo(Request $request)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'postcode'              => array('regex:/^[a-zA-Z]{1,2}([0-9]{1,2}|[0-9][a-zA-Z])\s*[0-9][a-zA-Z]{2}$/')
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }
        else
        {
            $user = Auth::user();

            if ($request->full_name)
                $user->full_name = $request->full_name;

            if ($request->known_as)
                $user->known_as = $request->known_as;

            if ($request->postcode)
                $user->postcode = $request->postcode;

            $user->save();

            return response()->json(['success' => ""], 200);
        }    
    }

    public function UpdateProfilePhoto(Request $request)
    {
        $messages = array();

        $validator = Validator::make($request->all(),
        [
            'profile_photo'          => 'required'
        ], $messages);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 200);            
        }
        else
        {
            $user = Auth::user();
            $user->profile_photo = base64_encode(Image::make($request->profile_photo)->fit(500, 500, function ($constraint)
                                                                                        {
                                                                                            $constraint->upsize();
                                                                                        })->encode()->encoded);
            $user->save();

            return response()->json(['success' => ""], 200);
        }
    }
}