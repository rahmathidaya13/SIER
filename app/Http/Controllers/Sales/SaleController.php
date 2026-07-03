<?php

namespace App\Http\Controllers\Sales;

use App\Traits\UserScope;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\SaleRepository;

class SaleController extends Controller
{
    use UserScope;
    protected $saleRepository;
    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
    }
    public function index(Request $request)
    {
        $filters = $request->only([
            'keyword',
            'limit',
            'order_by',
            'page',
        ]);
        $sales = $this->saleRepository->getCached($this->scopeId(auth()->user()), $filters);
        return inertia(
            'Sales/Index',
            [
                'sales' => $sales,
                'filters' => $filters,
            ]
        );
    }
}
