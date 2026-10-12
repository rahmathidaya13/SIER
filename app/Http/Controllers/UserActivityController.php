<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    public function ping(Request $request)
    {
        $now = now()->timestamp;

        // Update the last activity timestamp in the session
        $request->session()->put('last_user_activity_at', $now);

        return response()->json(['success' => true, 'lastActivityAt' => $now]);
    }
}
