<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(10);
        return inertia('Notification/Index', [
            'notifications' => $notifications
        ]);
    }
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead(); // Fungsi bawaan Laravel

        return redirect()->back(); // Kembali ke halaman, Inertia akan mengurus sisanya!
    }

    public function markAllRead(Request $request)
    {
        // dd($request->all());
        // Tandai SEMUA notifikasi yang belum dibaca milik user ini menjadi sudah dibaca
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('message', 'Semua notifikasi telah ditandai sudah dibaca.');
    }
}
