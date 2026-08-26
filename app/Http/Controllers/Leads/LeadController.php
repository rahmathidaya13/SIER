<?php

namespace App\Http\Controllers\Leads;

use App\Models\User;
use App\Models\LeadModel;
use App\Notifications\LeadCreatedNotification;
use App\Traits\UserScope;
use App\Models\ChannelModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Traits\LeadsValidation;
use App\Services\Lead\LeadServices;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Notifications\LeadDeleteNotification;
use App\Traits\BranchMutationHelper;
use Illuminate\Support\Facades\Gate;
use App\Repository\Sale\SaleRepository;
use App\Repository\Lead\LeadsRepository;
use App\Traits\ActivityMessageFormatter;
use Illuminate\Support\Facades\Notification;
use App\Repository\Channel\ChannelRepository;
use App\Services\Lead\LeadConversionServices;
use Illuminate\Validation\ValidationException;
use App\Services\Authorization\ScopeResolverService;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use UserScope, BranchMutationHelper, LeadsValidation, ActivityMessageFormatter;
    protected $repository;
    protected $leadServices;
    protected $leadConversionServices;
    protected $scopeResolverService = ScopeResolverService::class;
    public function __construct(LeadsRepository $leadRepository, LeadServices $leadServices, LeadConversionServices $leadConversionServices)
    {
        $this->repository = $leadRepository;
        $this->leadServices = $leadServices;
        $this->leadConversionServices = $leadConversionServices;
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', LeadModel::class);

        $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'periode' => 'nullable|date|date_format:Y-m',
            'status' => 'nullable|string|in:all,active,new,follow_up,closed_won,closed_lost',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only([
            'keyword',
            'limit',
            'periode',
            'status',
            'order_by',
            'page',

        ]);
        $filters = app(ScopeResolverService::class)->filters($filters);

        $leads = $this->repository->getCached($filters['scope'], $filters);
        $channels = ChannelModel::select('id_channel', 'name', 'icon')->get();
        $products = ProductModel::select('id_product', 'name', 'image_link', 'image_path')->get();
        return inertia(
            'Leads/Index',
            [
                'leads' => $leads,
                'filters' => $filters,
                'channels' => $channels,
                'products' => $products,
            ]
        );
    }

    public function store(Request $request)
    {
        // policy lead
        $response = Gate::inspect('create', LeadModel::class);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message()
            ]);
        }

        $user = auth()->user();
        $validated = $this->validateLeads($request->all());

        if ($request['status'] === 'CLOSED_WON' && $request['payment_status'] === 'DP') {
            $minimalDp = $request['deal_price'] * 0.5;
            if ($request['paid_amount'] < $minimalDp) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Uang muka (DP) minimal harus 50% (Minimal: Rp ' . number_format($minimalDp, 0, ',', '.') . ')'
                ]);
            }
        }

        try {
            $leads = $this->leadServices->store($validated, $user);

            $branchUsers = User::where('id_branch', $leads->id_branch)
                ->where('id', '!=', $user->id)
                ->get();

            $admins = User::role(['super_admin', 'developer'])->get();
            $recipients = $branchUsers->merge($admins)->unique('id');
            Notification::send($recipients, new LeadCreatedNotification($leads, $user->name));
            $this->invalidateCaches(
                [LeadsRepository::class, SaleRepository::class, ChannelRepository::class],
                ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]
            );
            return redirect()->back()->with([
                'message' => 'Lead dari ' . strtoupper($request['name']) . ' berhasil ditambahkan',
            ]);
        } catch (\Exception $error) {
            $errorMessage = config('app.debug') ? $error->getMessage() : 'Terjadi kesalahan pada server. Silakan coba lagi.';
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $errorMessage]);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $validated = $this->validateLeads($request->all(), $id);

        $leads = LeadModel::find($id);

        if (!$leads) {
            throw ValidationException::withMessages([
                'error' => 'Data lead tidak ditemukan atau sudah dihapus.'
            ]);
        }

        // cek policy
        $response = Gate::inspect('update', $leads);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message()
            ]);
        }

        try {
            $this->leadServices->update($leads, $validated, $user);
            $this->invalidateCaches(
                [LeadsRepository::class, SaleRepository::class, ChannelRepository::class],
                ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]
            );
            return redirect()->back()->with('message', 'Lead ' . $leads->name . ' berhasil diperbarui!');
        } catch (\Exception $error) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui data: ' . $error->getMessage()]);
        }
    }

    public function show(string $id)
    {
        $leads = LeadModel::select([
            'id_lead',
            'id_user',
            'id_channel',
            'name',
            'phone',
            'email',
            'status',
            'temperature',
            'customer_type',
            'notes',
            'follow_up_date'
        ])->with([
            'user:id,name',
            'leadProduct.product:id_product,name,image_link,image_path',
            'channel:id_channel,name,icon',

            // Batasi aktivitas maksimal 10 agar memori server tidak meledak
            'activity' => function ($query) {
                $query->select('id_lead_activity', 'id_lead', 'id_user', 'updated_by', 'description', 'created_at', 'updated_at')
                    ->latest()
                    ->limit(10);
            },
            'activity.user:id,name',
            'activity.user.roles:id,name',
            'activity.userUpdate:id,name',
            'activity.userUpdate.roles:id,name',
        ])->findOrFail($id);

        return new LeadResource($leads);
    }
    public function destroy(string $id)
    {
        $user = auth()->user();
        $leads = LeadModel::find($id);



        if (!$leads) {
            throw ValidationException::withMessages([
                'error' => 'Data lead tidak ditemukan atau sudah dihapus.'
            ]);
        }

        $response = Gate::inspect('delete', $leads);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'error' => $response->message()
            ]);
        }

        $leads->delete();

        $branchUsers = User::where('id_branch', $leads->id_branch)
            ->where('id', '!=', auth()->id())
            ->get();
        $admins = User::role(['super_admin', 'developer'])->get();

        $recipients = $branchUsers->merge($admins)->unique('id');
        Notification::send($recipients, new LeadDeleteNotification($leads, $user->name));

        $this->invalidateCaches(
            [LeadsRepository::class, SaleRepository::class, ChannelRepository::class],
            ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]
        );
        return redirect()->back()->with('message', 'Lead ' . $leads->name . ' berhasil dihapus');
    }
}
