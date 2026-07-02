<?php

namespace App\Http\Controllers\Leads;

use Exception;
use App\Models\LeadModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use App\Traits\ImmutableFieldValidator;
use App\Services\LeadConversionServices;
use App\Traits\LeadConversionValidation;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Leads\LeadsRepository;

class LeadConversionController extends Controller
{
    use BranchMutationHelper, LeadConversionValidation, ImmutableFieldValidator;
    public function store(Request $request, LeadModel $leads, LeadConversionServices $services)
    {
        $this->validateLeadConversion($request->all());

        $this->__validate($leads, $request['status'], [
            'status' => [
                'locked_value' => 'CLOSED_WON',
                'message' => 'Tidak dapat mengubah status prospek yang sudah disahkan menjadi status lain.',
                'error_key' => 'status'
            ],
        ]);
        try {
            $services->convertLeadToSales($leads, $request->all());
            $user = auth()->user();
            $this->invalidateCaches(LeadsRepository::class, ['global', $user->id_branch]);
            return redirect()->back()->with('success', 'Transaksi atas nama ' . $leads->name . ' sudah berhasil closing.');
        } catch (ValidationException $error) {
            throw $error;
        } catch (Exception $err) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data transaksi: ' . $err->getMessage());
        }
    }
}
