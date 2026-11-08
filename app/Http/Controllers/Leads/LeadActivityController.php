<?php

namespace App\Http\Controllers\Leads;

use Illuminate\Http\Request;
use App\Models\LeadActivityModel;
use App\Traits\InvalidatesCaches;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadActivityRequest;
use App\Repository\Sale\SaleRepository;
use App\Repository\User\UserRepository;
use App\Repository\Lead\LeadsRepository;
use App\Repository\Channel\ChannelRepository;
use App\Support\ScopeKey;

class LeadActivityController extends Controller
{
    use InvalidatesCaches;
    public function store(LeadActivityRequest $request, string $id)
    {

        $validated = $request->validated(); // Ambil data dari request

        $user = auth()->user(); // Ambil user yang sedang login

        $leadActivity = DB::transaction(function () use ($id, $validated, $user) {

            $activity = LeadActivityModel::create([
                'id_lead' => $id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'description' => $validated['description']
            ]);


            $lead = $activity->lead; // Tarik Data Lead Utama (Induk)
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

            return $activity;
        });


        $this->invalidateCaches(
            [
                UserRepository::class,
                LeadsRepository::class,
                SaleRepository::class,
                ChannelRepository::class
            ],
            [
                ScopeKey::user($user->id),
                ScopeKey::user($leadActivity->created_by),
                ScopeKey::branch($user->id_branch),
                ScopeKey::GLOBAL,
                ScopeKey::GLOBAL_SUPER_ADMIN,
            ]
        );

        return redirect()->back()->with('modal_message', 'Aktivitas Follow Up berhasil ditambahkan!');
    }
    public function update(LeadActivityRequest $request, string $id)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $leadActivity = DB::transaction(function () use ($id, $validated, $user) {
            $leadActivity = LeadActivityModel::findOrFail($id);
            $leadActivity->update([
                'updated_by' => $user->id,
                'description' => $validated['description']
            ]);
            return $leadActivity;
        });


        $this->invalidateCaches(
            [
                UserRepository::class,
                LeadsRepository::class,
                SaleRepository::class,
                ChannelRepository::class
            ],
            [
                ScopeKey::user($user->id),
                ScopeKey::user($leadActivity->created_by),
                ScopeKey::user($leadActivity->updated_by),
                ScopeKey::branch($user->id_branch),
                ScopeKey::GLOBAL,
                ScopeKey::GLOBAL_SUPER_ADMIN,
            ]
        );
        return redirect()->back();
    }
}
