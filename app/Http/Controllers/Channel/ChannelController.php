<?php

namespace App\Http\Controllers\Channel;

use App\Traits\UserScope;
use App\Models\ChannelModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use Illuminate\Support\Facades\Gate;
use App\Repository\Channel\ChannelRepository;
use Illuminate\Validation\ValidationException;
use App\Services\Authorization\ScopeResolverService;

class ChannelController extends Controller
{
    use UserScope, BranchMutationHelper;
    protected $repository;
    public function __construct(ChannelRepository $channelRepository)
    {
        $this->repository = $channelRepository;
        $this->middleware('role:developer|super_admin');
    }
    public function index(Request $request)
    {
        $this->authorize('view', ChannelModel::class);

        $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only([
            'keyword',
            'limit',
            'order_by',
            'page',
        ]);

        $filters = app(ScopeResolverService::class)->filters($filters);
        $channels = $this->repository->getCached($filters['scope'], $filters);
        return inertia('Channels/Index', [
            'channels' => $channels,
            'filters' => $filters
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name' => 'required|unique:channels,name',
            'code' => 'required|unique:channels,code',
            'icon' => 'required',
        ], [
            'name.required' => 'Nama channel tidak boleh kosong',
            'name.unique' => 'Nama channel sudah ada',
            'code.required' => 'Kode channel tidak boleh kosong',
            'code.unique' => 'Kode channel sudah ada',
            'icon.required' => 'Icon channel harus dipilih',
        ]);

        $response = Gate::inspect('create', ChannelModel::class);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message()
            ]);
        }

        ChannelModel::create([
            'name' => ucwords($request['name']),
            'code' => strtoupper($request['code']),
            'icon' => $request['icon'],
        ]);
        $this->invalidateCaches([ChannelRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('message', 'Channel ' . $request['name'] . ' berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $channel = ChannelModel::with(['user', 'leads'])
            ->findOrFail($id);
        return response()->json($channel);
    }

    public function update(Request $request, ChannelModel $channel)
    {
        $user = auth()->user();
        $request->validate([
            'name' => ['required', Rule::unique('channels', 'name')->ignore($channel, 'id_channel')],
            'code' => ['required', Rule::unique('channels', 'code')->ignore($channel, 'id_channel')],
            'icon' => 'required',
        ], [
            'name.required' => 'Nama channel tidak boleh kosong',
            'name.unique' => 'Nama channel sudah ada',
            'code.required' => 'Kode channel tidak boleh kosong',
            'code.unique' => 'Kode channel sudah ada',
            'icon.required' => 'Icon channel harus dipilih',
        ]);

        $response = Gate::inspect('update', $channel);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message()
            ]);
        }

        $channel->update([
            'name' => ucwords($request['name']),
            'code' => strtoupper($request['code']),
            'icon' => $request['icon'],
        ]);
        $this->invalidateCaches([ChannelRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('message', 'Channel ' . $channel->name . ' berhasil diperbarui!');
    }


    public function destroy(ChannelModel $channel)
    {
        $response = Gate::inspect('delete', $channel);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message()
            ]);
        }

        $user = auth()->user();

        if (!$user->hasRole('super_admin')) {
            throw ValidationException::withMessages([
                'error' => 'Anda tidak memiliki akses untuk menghapus channel!'
            ]);
        }

        $channel->delete();
        $this->invalidateCaches([ChannelRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('message', 'Channel ' . $channel->name . ' berhasil dihapus!');
    }
}
