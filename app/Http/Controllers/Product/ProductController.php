<?php

namespace App\Http\Controllers\Product;

use App\Traits\UserScope;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Product\ProductRepository;
use App\Services\Authorization\ScopeResolverService;

class ProductController extends Controller
{
    use UserScope;
    protected $repository;
    protected $scopeResolverService;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
        $this->scopeResolverService = new ScopeResolverService();
    }
    public function index(Request $request)
    {
        $filters = $request->only([
            'keyword',
            'limit',
            'order_by',
            'page',
        ]);
        $filters = $this->scopeResolverService->filters($filters);
        $products = $this->repository->getCached($this->scopeId(auth()->user()), $filters);
        $category = ProductModel::select('category')->distinct()->get();
        return inertia('Product/Index', [
            'products' => $products,
            'category' => $category,
            'filters' => $filters
        ]);
    }

    public function show(ProductModel $product)
    {
        $product->load(['prices', 'creator']);
        return response()->json($product);
    }

    public function store(Request $request) {}

    public function update(Request $request, ProductModel $product) {}

    public function destroy(ProductModel $product) {}
    public function productSearch(Request $request)
    {
        $keyword = $request->input('keyword');
        $products = ProductModel::with('prices')
            ->select('id_product', 'name', 'image_link', 'image_path')
            ->when($keyword, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id_product', 'like', "%{$search}%");
            })
            ->limit(10) // HANYA AMBIL 10 TERATAS!
            ->get();

        return response()->json($products);
    }
}
