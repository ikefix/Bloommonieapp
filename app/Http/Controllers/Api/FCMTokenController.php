<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FCMTokenController extends Controller
{
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'fcm_token' => 'required|string',
    //     ]);

    //     Auth::user()->update([
    //         'fcm_token' => $request->fcm_token,
    //     ]);

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'FCM token saved',
    //     ]);
    // }

public function saveFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
    ]);

    $user = $request->user();

    logger()->info([
        'user_id' => optional($user)->id,
        'token_length' => strlen($request->fcm_token),
    ]);

    $user->fcm_token = $request->fcm_token;
    $user->save();

    return response()->json([
        'status' => true,
    ]);
}
}