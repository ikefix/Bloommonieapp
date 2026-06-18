<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;

class NotificationController extends Controller
{
    public function index()
{
    $user = Auth::user();

    $unreadNotifications = $user->unreadNotifications()->get();
    $readNotifications = $user->readNotifications()->get();

    $view = match ($user->role) {
        'admin' => 'admin.notifications',
        'manager' => 'manager.notification',
        default => abort(403),
    };

    return view($view, compact('unreadNotifications', 'readNotifications'));
}

 

    // This method is to mark notifications as read when viewed
    public function markAsRead($notificationId)
{
    $user = Auth::user();

    $notification = $user->notifications()->where('id', $notificationId)->first();

    if ($notification) {
        $notification->markAsRead();
    }

    return back();
}

    public function destroy($id)
{
    $notification = Auth::user()->notifications()->where('id', $id)->first();

    if ($notification) {
        $notification->delete();
        return back()->with('success', 'Deleted');
    }

    return back()->with('error', 'Not found');
}
}
