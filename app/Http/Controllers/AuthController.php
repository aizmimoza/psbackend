<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller {
    // register
    public function signup(Request $request) {
        // validation rules
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|string|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                // Password::min(8)->mixedCase()->numbers()->symbols()
            ]
        ]);
        
        // create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        // generate token
        $token = $user->createToken('main')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ], 201);
    }


    public function signin(Request $request) {
        // validation rules
        $credentials = $request->validate([
            'email' => 'required|email|string|exists:users,email',
            'password' => 'required',
            'remember' => 'boolean'
        ]);

        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        // verification
        if( !Auth::attempt($credentials, $remember) ) {
            return response([
                'error' => "Votre adresse mail ou mot de passe est incorrect."
            ], 422);
        }
        
        // auth and generate token
        $user = Auth::user();
        $token = $user->createToken('main')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ], 201);
    }


    public function signout() {
        /** @var User $user */
        // get the current user
        $user = Auth::user();

        // delete current user token
        $user->currentAccessToken()->delete();

        return response([
            'success' => true,
        ]);
    }

}
