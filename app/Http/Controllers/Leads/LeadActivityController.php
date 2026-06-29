<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\LeadActivityModel;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function update(Request $request, string $id)
    {
        $request->validate([
            'description' => 'required'
        ]);
        $leadActivity = LeadActivityModel::findOrFail($id);
        $leadActivity->update([
            'description' => $request['description']
        ]);
        return redirect()->back();
    }
}
