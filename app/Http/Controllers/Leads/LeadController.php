<?php

namespace App\Http\Controllers\Leads;

use App\Models\User;
use App\Models\LeadModel;
use App\Traits\UserScope;
use Illuminate\Support\Arr;
use App\Models\ChannelModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Traits\LeadsValidation;
use App\Models\LeadActivityModel;
use App\Services\Lead\LeadServices;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use Illuminate\Support\Facades\Gate;
use App\Repository\Sale\SaleRepository;
use App\Repository\User\UserRepository;
use App\Repository\Lead\LeadsRepository;
use App\Traits\ActivityMessageFormatter;
use App\Http\Resources\LeadActivityResource;
use App\Models\CustomerModel;
use Illuminate\Support\Facades\Notification;
use App\Repository\Channel\ChannelRepository;
use App\Services\Lead\LeadConversionServices;
use Illuminate\Validation\ValidationException;
use App\Notifications\Lead\LeadCreateNotification;
use App\Notifications\Lead\LeadDeleteNotification;
use App\Notifications\Lead\LeadUpdateNotification;
use App\Services\Authorization\ScopeResolverService;
use App\Support\ScopeKey;
use App\Traits\InvalidatesCaches;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use UserScope, LeadsValidation, ActivityMessageFormatter, InvalidatesCaches;
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
        $filters = app(ScopeResolverService::class)->filters($filters); // Gunakan ScopeResolverService untuk memproses filter berdasarkan scope yang diperlukan
        $cleanFilters = Arr::except($filters, ['scope', 'page']); // Hapus 'scope' dan 'page' dari $filters sebelumnya
        $leads = $this->repository->getCached($filters['scope'], $cleanFilters, $filters['page'] ?? 1);
        $channels = ChannelModel::select('id_channel', 'name', 'icon')->get();
        return inertia(
            'Leads/Index',
            [
                'leads' => $leads,
                'filters' => $filters,
                'channels' => $channels,
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

            $recipients = $this->getLeadNotificationRecipients($leads, $user);
            Notification::send($recipients, new LeadCreateNotification($leads, $user));

            $this->invalidateCaches(
                [
                    UserRepository::class,
                    LeadsRepository::class,
                    SaleRepository::class,
                    ChannelRepository::class
                ],
                [
                    ScopeKey::user($leads->id_user),
                    ScopeKey::branch($leads->id_branch),
                    ScopeKey::GLOBAL,
                    ScopeKey::GLOBAL_SUPER_ADMIN,
                ]
            );

            return redirect()->back()->with([
                'message' => 'Lead dari ' . strtoupper($leads->customer->name) . ' berhasil ditambahkan',
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

        $oldUserId = $leads->id_user;
        $oldBranchId = $leads->id_branch;

        try {
            $leads = $this->leadServices->update($leads, $validated, $user);
            $recipients = $this->getLeadNotificationRecipients(
                $leads,
                $user
            );

            // send notification via echo
            Notification::send(
                $recipients,
                new LeadUpdateNotification(
                    $leads,
                    $user
                )
            );
            $this->invalidateCaches(
                [
                    UserRepository::class,
                    LeadsRepository::class,
                    SaleRepository::class,
                    ChannelRepository::class
                ],
                [
                    ScopeKey::user($oldUserId),
                    ScopeKey::branch($oldBranchId),
                    ScopeKey::user($leads->id_user),
                    ScopeKey::branch($leads->id_branch),
                    ScopeKey::GLOBAL,
                    ScopeKey::GLOBAL_SUPER_ADMIN
                ]
            );
            return redirect()->back()->with('message', 'Lead ' . ucwords($leads->customer->name) . ' berhasil diperbarui!');
        } catch (\Exception $error) {
            $errorMessage = config('app.debug')
                ? $error->getMessage()
                : 'Terjadi kesalahan pada server. Silakan coba lagi.';
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui data: ' . $errorMessage]);
        }
    }

    public function show(string $id)
    {
        $leads = LeadModel::query()
            ->select([
                'id_lead',
                'id_user',
                'id_channel',
                'id_customer',
                'status',
                'temperature',
                'notes',
                'follow_up_date',
                'closed_at'
            ])
            ->with([
                'user:id,name',
                'user.roles:id,name',
                'channel:id_channel,name,icon',
                'leadProduct:id_lead,id_product',
                'leadProduct.product:id_product,name,image_link,image_path',
                'customer:id_customer,customer_code,name,phone,email,customer_tag,city,address,is_member',
            ])
            ->withCount('leadProduct')
            ->findOrFail($id);

        return LeadResource::make($leads); // Gunakan LeadResource untuk mengubah data menjadi format JSON yang sesuai
    }

    public function activities(string $id)
    {
        $activities = LeadActivityModel::query()
            ->where('id_lead', $id)
            ->select([
                'id_lead_activity',
                'id_lead',
                'id_user',
                'updated_by',
                'description',
                'source',
                'created_at',
                'updated_at',
            ])
            ->with([
                'user:id,name',
                'user.roles:id,name',

                'userUpdate:id,name',
                'userUpdate.roles:id,name',
            ])
            ->latest('created_at')
            ->paginate(10);

        return LeadActivityResource::collection($activities);
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

        if ($leads->sales()->exists()) {
            throw ValidationException::withMessages([
                'error' => 'Tidak dapat menghapus lead yang memiliki transaksi.'
            ]);
        }

        $response = Gate::inspect('delete', $leads);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'error' => $response->message()
            ]);
        }

        try {
            $leadId = $leads->id_lead;
            $leadName = $leads->name;

            $recipients = $this->getLeadNotificationRecipients($leads, $user);
            $this->leadServices->delete($leads, $user);
            Notification::send($recipients, new LeadDeleteNotification($leadId, $leadName, $user));

            $this->invalidateCaches(
                [
                    UserRepository::class,
                    LeadsRepository::class,
                    SaleRepository::class,
                    ChannelRepository::class
                ],
                [
                    'user_' . ScopeKey::user($leads->id_user),
                    'branch_' . ScopeKey::branch($leads->id_branch),
                    ScopeKey::GLOBAL,
                    ScopeKey::GLOBAL_SUPER_ADMIN,
                ]
            );
            return redirect()->back()->with('message', 'Lead ' . ucwords($leads->name) . ' berhasil dihapus');
        } catch (\Exception $error) {
            $errorMessage = config('app.debug')
                ? $error->getMessage()
                : 'Terjadi kesalahan pada server. Silakan coba lagi.';

            return redirect()
                ->back()
                ->withErrors([
                    'error' => $errorMessage
                ]);
        }
    }

    private function getLeadNotificationRecipients(LeadModel $lead, User $actor)
    {
        $branchUsers = User::query()
            ->where('id_branch', $lead->id_branch)
            ->where('id', '!=', $actor->id)
            ->get();

        $admins = User::role(['super_admin', 'developer'])->get();

        return $branchUsers->merge($admins)->unique('id');
    }

    public function productSearch(Request $request)
    {
        $keyword = $request->input('keyword');
        $products = ProductModel::with('prices:id_product,status')
            ->whereHas('prices', function ($q) {
                $q->where('status', 'published');
            })
            ->select('id_product', 'name', 'image_link', 'image_path', 'category')
            ->when(!empty($keyword), function ($q) use ($keyword) {
                $cleanSearch = addcslashes($keyword, '%_');
                $q->where('name', 'like', "%{$cleanSearch}%");
            })
            ->limit(10) // HANYA AMBIL 10 TERATAS!
            ->get()
            ->map(function ($product) {
                $product->image_link = !empty($product->image_link)
                    ? $product->image_link
                    : (
                        !empty($product->image_path)
                        ? asset('storage/' . ltrim($product->image_path, '/'))
                        : null
                    );

                return $product;
            });

        return response()->json($products);
    }
    public function customerSearch(Request $request)
    {
        $user = auth()->user();
        $keyword = trim((string) $request->input('keyword', ''));
        $query = CustomerModel::query()
            ->select([
                'id_customer',
                'customer_code',
                'name',
                'phone',
                'email',
                'customer_tag',
                'address',
                'city',
                'is_member',
                'created_of_branch',
            ])
            ->where('status', 'active');

        if (!$user->hasRole(['super_admin', 'developer'])) {
            $query->where('created_of_branch', $user->id_branch);
        }

        $query->when(!empty($keyword), function ($q) use ($keyword) {
            $cleanSearch = addcslashes($keyword, '%_');
            $q->where('name', 'like', "%{$cleanSearch}%")
                ->orWhere('phone', 'like', "%{$cleanSearch}%");
        });


        $customer = $query->limit(10)->get();
        return response()->json($customer);
    }
}
