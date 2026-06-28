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
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['status' => true, 'message' => 'FCM token saved']);
    }
}