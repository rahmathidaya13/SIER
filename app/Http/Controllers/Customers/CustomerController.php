<?php

namespace App\Http\Controllers\Customers;

use App\Repository\Customers\CustomerRepository;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Authorization\ScopeResolverService;

class CustomerController extends Controller
{
    protected $repository;
    public function __construct()
    {
        $this->repository = new CustomerRepository();
    }
    public function index(Request $request)
    {
        $filters = $request->only([
            'keyword',
            'limit',
            'order_by',
            'page',

        ]);
        $filters = app(ScopeResolverService::class)->filters($filters); // Gunakan ScopeResolverService untuk memproses filter berdasarkan scope yang diperlukan
        $cleanFilters = Arr::except($filters, ['scope', 'page']); // Hapus 'scope' dan 'page' dari $filters sebelumnya
        $customer = $this->repository->getCached($filters['scope'], $cleanFilters, $filters['page'] ?? 1);
        return inertia(
            'Customers/Index',
            [
                'customers' => $customer,
                'filters' => $filters,
            ]
        );
    }
}
