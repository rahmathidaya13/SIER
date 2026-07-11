<?php

namespace App\Http\Controllers\Channel;

use App\Traits\UserScope;
use App\Models\ChannelModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Channel\ChannelRepository;
use App\Services\Authorization\ScopeResolverService;

class ChannelController extends Controller
{
    use UserScope, BranchMutationHelper;
    protected $repository;
    public function __construct(ChannelRepository $channelRepository)
    {
        $this->repository = $channelRepository;
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        $filters = $request->only([
            'keyword',
            'limit',
            'order_by',
            'page',

        ]);

        // inject id_branch and id_user
        $filters = app(ScopeResolverService::class)->filters($filters);
        $channels = $this->repository->getCached($this->scopeId($user), $filters);
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
        ChannelModel::create([
            'name' => ucwords($request['name']),
            'code' => strtoupper($request['code']),
            'icon' => $request['icon'],
        ]);
        $this->invalidateCaches([ChannelRepository::class], ['global', $user->id_branch]);
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
        $channel->update([
            'name' => ucwords($request['name']),
            'code' => strtoupper($request['code']),
            'icon' => $request['icon'],
        ]);
        $this->invalidateCaches([ChannelRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Channel ' . $channel->name . ' berhasil diperbarui!');
    }


    public function destroy(ChannelModel $channel)
    {
        $user = auth()->user();

        if (!$user->hasRole('super_admin')) {
            throw ValidationException::withMessages([
                'error' => 'Anda tidak memiliki akses untuk menghapus channel!'
            ]);
        }

        $channel->delete();
        $this->invalidateCaches([ChannelRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Channel ' . $channel->name . ' berhasil dihapus!');
    }
}
