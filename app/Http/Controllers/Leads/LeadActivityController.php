<?php

namespace App\Http\Controllers\Leads;

use Illuminate\Http\Request;
use App\Models\LeadActivityModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use App\Repository\Lead\LeadsRepository;
use App\Repository\Channel\ChannelRepository;
use App\Http\Controllers\Sales\SaleRepository;

class LeadActivityController extends Controller
{
    use BranchMutationHelper;
    public function store(Request $request, string $id)
    {

        $request->validate(
            [
                'description' => 'required'
            ],
            [
                'description.required' => 'Aktivitas Follow Up tidak boleh kosong'
            ]
        );

        DB::beginTransaction();
        try {
            $activity = LeadActivityModel::create([
                'id_lead' => $id,
                'id_user' => auth()->user()->id,
                'description' => $request['description']
            ]);

            // Tarik Data Lead Utama (Induk)
            $lead = $activity->lead;
            $leadUpdates = []; // Siapkan data untuk pembaruan (Array)

            if ($lead->status === 'NEW') {
                $leadUpdates['status'] = 'FOLLOW_UP';
            }

            if (is_null($lead->follow_up_date)) {
                $leadUpdates['follow_up_date'] = now();
            }

            if (!empty($leadUpdates)) {
                $lead->update($leadUpdates);
            }

            DB::commit();

            $user = auth()->user();
            $this->invalidateCaches(
                [LeadsRepository::class, SaleRepository::class, ChannelRepository::class],
                ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]
            );

            return redirect()->back()->with('modal_message', 'Aktivitas Follow Up berhasil ditambahkan!');
        } catch (\Exception $error) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data aktivitas follow up: ' . $error->getMessage()]);
        }
    }
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

        $user = auth()->user();
        $this->invalidateCaches(
            [LeadsRepository::class, SaleRepository::class, ChannelRepository::class],
            ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]
        );
        return redirect()->back();
    }
}
