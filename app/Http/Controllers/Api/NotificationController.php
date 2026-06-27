<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // GET ALL NOTIFICATIONS (unread + read)
    public function index()
    {
        $user = Auth::user();

        $unread = $user->unreadNotifications()->get();
        $read   = $user->readNotifications()->get();

        return response()->json([
            'status' => true,
            'data'   => [
                'unread'        => $unread,
                'read'          => $read,
                'unread_count'  => $unread->count(),
                'total_count'   => $unread->count() + $read->count(),
            ],
        ]);
    }

    // MARK SINGLE NOTIFICATION AS READ
    public function markAsRead($id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status'  => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'status'  => true,
            'message' => 'Notification marked as read',
        ]);
    }

    // MARK ALL NOTIFICATIONS AS READ
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'status'  => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    // DELETE SINGLE NOTIFICATION
    public function destroy($id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status'  => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Notification deleted',
        ]);
    }

    // DELETE ALL NOTIFICATIONS
    public function destroyAll()
    {
        Auth::user()->notifications()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'All notifications cleared',
        ]);
    }
}