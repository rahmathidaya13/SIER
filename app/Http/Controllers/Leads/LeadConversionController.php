<?php

namespace App\Http\Controllers\Leads;

use Exception;
use App\Models\User;
use App\Models\LeadModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use App\Repository\Sale\SaleRepository;
use App\Traits\ImmutableFieldValidator;
use App\Repository\Lead\LeadsRepository;
use App\Traits\LeadConversionValidation;
use Illuminate\Support\Facades\Notification;
use App\Repository\Channel\ChannelRepository;
use App\Services\Lead\LeadConversionServices;
use Illuminate\Validation\ValidationException;
use App\Notifications\Lead\LeadConversionNotification;
use App\Repository\User\UserRepository;

class LeadConversionController extends Controller
{
    use BranchMutationHelper, LeadConversionValidation, ImmutableFieldValidator;
    public function store(Request $request, LeadModel $leads, LeadConversionServices $services)
    {
        $validated = $this->validateLeadConversion($request->all());

        $this->__validate($leads, $request->all(), [
            'status' => [
                'locked_value' => 'CLOSED_WON',
                'message' => 'Tidak dapat mengubah status prospek yang sudah disahkan menjadi status lain.',
                'error_key' => 'status'
            ],
        ]);
        try {
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
                'global',
                'global_super_admin',
                'branch_' . $user->id_branch,
                'user_' . $user->id
            ]);

            return redirect()->back()->with('success', 'Transaksi atas nama ' . $leads->name . ' sudah berhasil closing.');
        } catch (ValidationException $error) {
            throw $error;
        } catch (Exception $err) {

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data transaksi: ' . $err->getMessage());
        }
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
