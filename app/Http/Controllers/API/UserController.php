<?php 

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Validator;

class UserController extends Controller
{
    public $successStatus = 200;

    public function DoRegister(Request $request)
    {
        $validator = Validator::make($request->all(),
        [
            'full_name'         => 'required',
            'known_as'          => 'required',
            'dob'               => 'required',
            'postcode'          => array('required', 'regex:/^[a-zA-Z]{1,2}([0-9]{1,2}|[0-9][a-zA-Z])\s*[0-9][a-zA-Z]{2}$/'),
            'phone_number'      => array('required', 'regex:/^(\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$/'),
            'email'             => 'required|email',
            'password'          => 'required',
            'confirm_password'  => 'required|same:password'
        ]);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 400);            
        }

        $input              = $request->all();
        $input['password']  = Hash::make($input['password']);
        $input['reg_ip']    = $request->ip();

        $user               = User::create($input);
        $success['token']   = $user->createToken('MyApp')->accessToken;
        $success['name']    = $user->name;

        return response()->json(['success' => $success], $this->successStatus);
    }

    public function DoLogin()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')]))
        {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            return response()->json(['success' => $success], $this->successStatus);
        }
        else
        {
            return response()->json(['error' => 'Email address or password invalid'], 400);
        }
    }

    public function GetDetails()
    {
        $user = Auth::user();
        return response()->json(['success' => $user], $this->successStatus);
    }
}