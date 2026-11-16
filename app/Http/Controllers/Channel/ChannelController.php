<?php

namespace App\Http\Controllers\Channel;

use App\Support\ScopeKey;
use App\Traits\UserScope;
use App\Models\BranchModel;
use App\Models\ChannelModel;
use Illuminate\Http\Request;
use App\Traits\InvalidatesCaches;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChannelRequest;
use App\Repository\Lead\LeadsRepository;
use App\Services\Channel\ChannelService;
use App\Repository\Channel\ChannelRepository;
use App\Repository\Sale\SaleRepository;

class ChannelController extends Controller
{
    use UserScope, InvalidatesCaches;
    protected $repository;
    protected $services;
    public function __construct(ChannelRepository $channelRepository)
    {
        $this->repository = $channelRepository;
        $this->services = new ChannelService;
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', ChannelModel::class);

        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'periode' => 'nullable|date|date_format:Y-m',
            'page' => 'nullable|integer|min:1',
        ]);

        $validated['periode'] = $validated['periode'] ?? now()->format('Y-m');

        $filters = $request->only(array_keys($validated));

        $user = auth()->user();
        $scopeKey = match (true) {
            $user->hasAnyRole(['developer', 'super_admin']) => ScopeKey::GLOBAL_SUPER_ADMIN,
            !empty($user->id_branch) => ScopeKey::branch($user->id_branch),
            default => ScopeKey::user($user->id),
        };

        $channels = $this->repository->getCached($scopeKey, $filters);
        return inertia('Channels/Index', [
            'channels' => $channels,
            'filters' => $filters
        ]);
    }

    public function store(ChannelRequest $request)
    {
        $this->authorize('create', ChannelModel::class);

        $user = auth()->user();
        $validated = $request->validated();
        $channel = $this->services->store($validated, $user);
        $this->clearChannelCaches($channel, $user, false);
        return redirect()->back()->with('message', 'Channel ' . ucwords($channel->name) . ' berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $channel = ChannelModel::query()
            ->select([
                'id_channel',
                'name',
                'code',
                'icon',
            ])
            ->findOrFail($id);
        $this->authorize('view', $channel);
        return response()->json($channel);
    }

    public function update(ChannelRequest $request, ChannelModel $channel)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $channels = $request->resolveChannelModel();

        $this->authorize('update', $channels, 'unauthorized_action');

        $this->services->update($channels, $validated, $user);

        $this->clearChannelCaches($channels, $user, true);
        return redirect()->back()->with('message', 'Channel ' . $channels->name . ' berhasil diperbarui!');
    }


    public function destroy(ChannelRequest $request, ChannelModel $channel)
    {
        $this->authorize('delete', $channel, 'unauthorized_action');
        $user = auth()->user();
        $this->services->delete($channel);
        $this->clearChannelCaches($channel, $user, true);
        return redirect()->back()->with('message', 'Channel ' . ucwords($channel->name) . ' berhasil dihapus!');
    }

    /**
     * Helper terpusat untuk invalidasi cache modul Channel dan relasinya.
     */
    protected function clearChannelCaches(ChannelModel $channel, $user, bool $includeRelated = true): void
    {
        $allBranchScopes = BranchModel::pluck('id_branch')
            ->map(fn($id) => ScopeKey::branch($id))
            ->all();

        $channelScopes = array_values(array_unique(array_filter([
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
            ...$allBranchScopes,
            $user?->id ? ScopeKey::user($user->id) : null,
        ])));


        if (!$includeRelated) {
            $this->invalidateCaches([ChannelRepository::class], $channelScopes);
            return;
        }

        $relatedScopes = array_values(array_unique(array_filter([
            ...$channelScopes,
            $channel->created_by ? ScopeKey::user($channel->created_by) : null,
            $channel->updated_by ? ScopeKey::user($channel->updated_by) : null,
        ])));



        $this->invalidateCaches([
            ChannelRepository::class,
            LeadsRepository::class,
            SaleRepository::class
        ], $relatedScopes);
    }
}
