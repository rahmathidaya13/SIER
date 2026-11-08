<?php

namespace App\Http\Controllers\Leads;

use App\Models\User;
use App\Models\LeadModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadConversionRequest;
use App\Repository\Sale\SaleRepository;
use App\Repository\Lead\LeadsRepository;
use App\Traits\InvalidatesCaches;
use Illuminate\Support\Facades\Notification;
use App\Repository\Channel\ChannelRepository;
use App\Services\Lead\LeadConversionServices;
use App\Notifications\Lead\LeadConversionNotification;
use App\Repository\User\UserRepository;
use App\Support\ScopeKey;

class LeadConversionController extends Controller
{
    use InvalidatesCaches;
    public function store(LeadConversionRequest $request, LeadModel $leads, LeadConversionServices $services)
    {
        $this->authorize('update', $leads);
        $validated = $request->validated();
        $user = auth()->user();

        $services->convertLeadToSales($leads, $validated, $user);

        $recipients = $this->getLeadNotificationRecipients(
            $leads,
            $user
        );

        Notification::send($recipients, new LeadConversionNotification(
            $leads,
            $user,
            $validated['payment_status'],
            $validated['deal_price'],
            $validated['paid_amount']
        ));

        // cache optimization
        $this->invalidateCaches([
            UserRepository::class,
            LeadsRepository::class,
            SaleRepository::class,
            ChannelRepository::class
        ], [
            ScopeKey::user($user->id), // user yang sedang login
            ScopeKey::user($leads->created_by), // user yang membuat lead
            ScopeKey::branch($user->id_branch), // cabang user yang sedang login
            ScopeKey::branch($leads->id_branch), // cabang lead yang dikonversi
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);

        return redirect()->back()->with('success', 'Transaksi atas nama ' . $leads->customer->name . ' sudah berhasil dikonversi.');
    }
    private function getLeadNotificationRecipients($lead, $actor)
    {
        $branchUsers = User::query()
            ->where('id_branch', $lead->id_branch)
            ->where('id', '!=', $actor->id)
            ->get();

        $admins = User::role([
            'super_admin',
            'developer',
        ])->get();

        return $branchUsers
            ->merge($admins)
            ->unique('id');
    }
}
