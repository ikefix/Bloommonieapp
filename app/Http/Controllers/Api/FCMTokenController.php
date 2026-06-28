<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FCMTokenController extends Controller
{
    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        Auth::user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'FCM token saved',
        ]);
    }

// public function saveFcmToken(Request $request)
// {
//     try {

//         $request->validate([
//             'fcm_token' => 'required|string',
//         ]);

//         $user = $request->user();

//         $user->fcm_token = $request->fcm_token;
//         $user->save();

//         return response()->json([
//             'status' => true,
//             'message' => 'Saved successfully',
//         ]);

//     } catch (\Throwable $e) {

//         return response()->json([
//             'error' => $e->getMessage(),
//             'file' => $e->getFile(),
//             'line' => $e->getLine(),
//             'trace' => $e->getTraceAsString(),
//         ], 500);
//     }
// }
}