<?php

namespace App\Http\Controllers\Channel;

use App\Traits\UserScope;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Authorization\ScopeResolverService;

class ChannelController extends Controller
{
    use UserScope;
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
