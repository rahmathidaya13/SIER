<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\LeadActivityModel;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function update(Request $request, string $id)
    {
        $request->validate(
            [
                'description' => 'required'
            ],
            [
                'description.required' => 'Aktivitas Follow Up tidak boleh kosong'
            ]
        );
        $leadActivity = LeadActivityModel::findOrFail($id);
        $leadActivity->update([
            'updated_by' => auth()->user()->id,
            'description' => $request['description']
        ]);

        return redirect()->back();
    }
}
