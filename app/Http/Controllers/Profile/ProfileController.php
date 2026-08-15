<?php

namespace App\Http\Controllers\Profile;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function show(string $id)
    {
        $query = User::with(['profile', 'branch','roles'])
            ->find($id);

        return response()->json($query);
    }
}
