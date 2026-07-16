<?php

namespace App\Http\Controllers\Product;

use App\Services\Product\ProductService;
use App\Traits\UserScope;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Product\ProductRepository;
use App\Services\Authorization\ScopeResolverService;
use App\Traits\BranchMutationHelper;
use App\Traits\ProductValidation;

class ProductController extends Controller
{
    use UserScope, BranchMutationHelper, ProductValidation;
    protected $repository;
    protected $scopeResolverService;
    protected $services;
    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
        $this->scopeResolverService = new ScopeResolverService();
        $this->services = new ProductService();
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

    public function store(Request $request)
    {
        $this->authorize('create', ProductModel::class);

        $this->validateProduct($request->all());
        $user = auth()->user();
        $product = $this->services->store($request->all());
        $this->invalidateCaches([ProductRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Produk baru ' . $product->name . ' berhasil ditambahkan!');
    }

    public function update(Request $request, ProductModel $product)
    {
        $user = auth()->user();
        $this->validateProduct($request->all());
        $this->services->update($product, $request->all());
        $this->invalidateCaches([ProductRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Perubahan data dari produk ' . $product->name . ' berhasil diperbarui!');
    }

    public function destroy(ProductModel $product)
    {
        $user = auth()->user();



        $this->services->delete($product);
        $this->invalidateCaches([ProductRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Data produk ' . $product->name . ' berhasil dihapus');
    }
    public function productSearch(Request $request)
    {
        $keyword = $request->input('keyword');
        $products = ProductModel::with('prices')
            ->select('id_product', 'name', 'image_link', 'image_path', 'category')
            ->when($keyword, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id_product', 'like', "%{$search}%");
            })
            ->limit(10) // HANYA AMBIL 10 TERATAS!
            ->get();

        return response()->json($products);
    }
}
