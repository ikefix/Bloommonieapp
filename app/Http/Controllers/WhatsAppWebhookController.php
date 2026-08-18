<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    // Handles Meta's verification handshake (GET request)
    public function verify(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN');

        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Verification failed', 403);
    }

    // Handles incoming messages (POST request)
    public function receive(Request $request)
    {
        $payload = $request->all();

        // Log it for now so we can see what Meta sends
        Log::info('WhatsApp webhook payload:', $payload);

        // We'll parse messages and reply here in the next step

        return response('EVENT_RECEIVED', 200);
    }
}