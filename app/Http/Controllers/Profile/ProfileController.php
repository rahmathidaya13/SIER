<?php

namespace App\Http\Controllers\Profile;

use App\Models\User;
use App\Traits\ProfileValidation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    use ProfileValidation;
    public function show(string $id)
    {
        $query = User::with(['profile', 'branch', 'roles'])
            ->find($id);

        return response()->json($query);
    }

    public function update(string $id)
    {
        $this->validateProfile(request()->all());
    }
}
