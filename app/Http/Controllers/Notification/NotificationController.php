<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Repository\Notifications\NotificationRepository;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $repository;
    public function __construct()
    {
        $this->repository = new NotificationRepository();
    }
    public function index(Request $request)
    {
        $filters = $request->only([
            'status',
            'limit',
            'page',
            'order_by'
        ]);

        $notifications = $this->repository->getData($filters);
        return inertia('Notification/Index', [
            'notifications' => $notifications,
            'filters' => $filters
        ]);
    }
    public function markAsRead(Request $request, $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead(); // Fungsi bawaan Laravel

        return redirect()->back(); // Kembali ke halaman, Inertia akan mengurus sisanya!
    }

    public function markAllRead(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
        ]);
        $user = auth()->user();
        $user->notifications()
            ->whereIn('id', $request['ids'])
            ->whereNull('read_at')
            ->get()
            ->markAsRead();
        // Tandai SEMUA notifikasi yang belum dibaca milik user ini menjadi sudah dibaca
        // $request->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('message', 'Semua notifikasi telah ditandai sudah dibaca.');
    }
}
