<?php

namespace App\Http\Controllers\Channel;

use App\Support\ScopeKey;
use App\Traits\UserScope;
use App\Models\ChannelModel;
use Illuminate\Http\Request;
use App\Traits\InvalidatesCaches;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChannelRequest;
use App\Repository\Lead\LeadsRepository;
use App\Repository\Channel\ChannelRepository;
use App\Services\Authorization\ScopeResolverService;

class ChannelController extends Controller
{
    use UserScope, InvalidatesCaches;
    protected $repository;
    public function __construct(ChannelRepository $channelRepository)
    {
        $this->repository = $channelRepository;
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', ChannelModel::class);

        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only(array_keys($validated));

        $filters = app(ScopeResolverService::class)->filters($filters);
        $channels = $this->repository->getCached($filters['scope'], $filters, $filters['page'] ?? 1);
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

        $channel = DB::transaction(function () use ($validated) {
            ChannelModel::create([
                'name' => ucwords($validated['name']),
                'code' => strtoupper($validated['code']),
                'icon' => $validated['icon'],
            ]);
        });

        $this->invalidateCaches([
            LeadsRepository::class,
            ChannelRepository::class,
        ], [
            ScopeKey::user($user->id),
            ScopeKey::user($channel->created_by),
            ScopeKey::branch($user->id_branch),
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);
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
        return response()->json($channel);
    }

    public function update(ChannelRequest $request, ChannelModel $channel)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $channels = $request->resolveChannelModel();

        $this->authorize('update', $channels, 'unauthorized_action');

        DB::transaction(function () use ($channels, $validated) {
            $channels->update([
                'name' => ucwords($validated['name']),
                'code' => strtoupper($validated['code']),
                'icon' => $validated['icon'],
            ]);
        });

        $this->invalidateCaches([
            LeadsRepository::class,
            ChannelRepository::class,
        ], [
            ScopeKey::user($user->id),
            ScopeKey::user($channels->created_by),
            ScopeKey::user($channels->updated_by),
            ScopeKey::branch($user->id_branch),
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);
        return redirect()->back()->with('message', 'Channel ' . $channels->name . ' berhasil diperbarui!');
    }


    public function destroy(ChannelModel $channel)
    {
        $this->authorize('delete', $channel, 'unauthorized_action');
        $user = auth()->user();
        DB::transaction(function () use ($channel) {
            $channel->delete();
        });

        $this->invalidateCaches([
            ChannelRepository::class,
            LeadsRepository::class,
        ], [
            ScopeKey::user($user->id),
            ScopeKey::user($channel->created_by),
            ScopeKey::branch($user->id_branch),
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);
        return redirect()->back()->with('message', 'Channel ' . ucwords($channel->name) . ' berhasil dihapus!');
    }
}
