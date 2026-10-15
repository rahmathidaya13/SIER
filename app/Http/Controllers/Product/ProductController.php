<?php

namespace App\Http\Controllers\Product;

use App\Support\ScopeKey;
use App\Traits\UserScope;
use Illuminate\Support\Arr;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Traits\InvalidatesCaches;
use App\Traits\ProductValidation;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use Illuminate\Support\Facades\Gate;
use App\Services\Product\ProductService;
use App\Repository\Product\ProductRepository;
use Illuminate\Validation\ValidationException;
use App\Services\Authorization\ScopeResolverService;

class ProductController extends Controller
{
    use UserScope, InvalidatesCaches, ProductValidation;
    protected $repository;
    protected $services;
    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
        $this->services = new ProductService();
    }
    public function index(Request $request)
    {
        $this->authorize('view', ProductModel::class);
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
        $cleanFilters = Arr::except($filters, ['scope', 'page']);
        $products = $this->repository->getCached($filters['scope'], $cleanFilters, $filters['page'] ?? 1);
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
        $response = Gate::inspect('create', ProductModel::class);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message()
            ]);
        }
        $this->validateProduct($request->all());
        $user = auth()->user();
        $product = $this->services->store($request->all());
        $this->invalidateCaches([ProductRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('message', 'Produk baru ' . $product->name . ' berhasil ditambahkan!');
    }

    public function update(Request $request, string $id)
    {
        $product = ProductModel::find($id);

        if (!$product) {
            throw ValidationException::withMessages([
                'error' => 'Data produk tidak ditemukan atau sudah dihapus.'
            ]);
        }

        $response = Gate::inspect('update', $product);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message()
            ]);
        }

        $user = auth()->user();
        $this->validateProduct($request->all());
        $this->services->update($product, $request->all());
        $this->invalidateCaches([
            ProductRepository::class
        ], [
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
            ScopeKey::branch($user->id_branch),
            ScopeKey::user($user->id)
        ]);
        return redirect()->back()->with('message', 'Perubahan data dari produk ' . $product->name . ' berhasil diperbarui!');
    }

    public function destroy(string $id)
    {
        $product = ProductModel::find($id);
        if (!$product) {
            throw ValidationException::withMessages([
                'error' => 'Data produk tidak ditemukan atau sudah dihapus.'
            ]);
        }

        $response = Gate::inspect('deleted', $product);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'error' => $response->message()
            ]);
        }

        $user = auth()->user();
        $this->services->delete($product);

        if (!$product->trashed()) {
            throw ValidationException::withMessages([
                'error' => "Sistem gagal menghapus data {$product->name} secara aman."
            ]);
        }
        $this->invalidateCaches([ProductRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('message', 'Data produk ' . $product->name . ' berhasil dihapus');
    }
    public function productSearch(Request $request)
    {
        $keyword = $request->input('keyword');
        $products = ProductModel::with('prices:id_product,status')
            ->whereHas('prices', function ($q) {
                $q->where('status', 'published');
            })
            ->select('id_product', 'name', 'image_link', 'image_path', 'category')
            ->when($keyword, function ($q, $search) {
                $cleanSearch = addcslashes($search, '%_');
                $q->where('name', 'like', "%{$cleanSearch}%")
                    ->orWhere('id_product', 'like', "%{$cleanSearch}%");
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
}
