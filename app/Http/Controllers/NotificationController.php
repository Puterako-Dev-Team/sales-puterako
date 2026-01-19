<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class NotificationController extends Controller
{
    public function markAsRead($id)
    {
        /** @var User $user */
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Redirect ke URL yang ada di notifikasi
        $url = $notification->data['url'] ?? route('dashboard');
        return redirect($url);
    }

    public function markAllAsRead()
    {
        /** @var User $user */
        $user = Auth::user();
        
        $user->unreadNotifications->markAsRead();
        
        return back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca');
    }
}