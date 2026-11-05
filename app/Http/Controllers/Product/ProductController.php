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
    public function __construct()
    {
        $this->repository = new ProductRepository();
        $this->services = new ProductService();
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', ProductModel::class);

        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only(array_keys($validated));
        // $filters = app(ScopeResolverService::class)->filters($filters);
        $products = $this->repository->getCached(ScopeKey::GLOBAL, $filters, $filters['page'] ?? 1);
        $category = ProductModel::select('category')->distinct()->get();
        return inertia('Product/Index', [
            'products' => $products,
            'category' => $category,
            'filters' => $filters
        ]);
    }


    public function show(ProductModel $product)
    {
        $product->load([
            'prices:id_product,base_price,discount_percentage,discount_price,status,valid_from,valid_until,price_type',
        ]);
        $this->authorize('view', $product);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $this->authorize('create', ProductModel::class, 'unauthorized_action');

        $user = auth()->user();
        $validated = $this->validateProduct($request->all());
        $product = $this->services->store($validated);
        $this->clearProductCaches($product, $user->id);
        return redirect()->back()->with('message', 'Produk baru ' . $product->name . ' berhasil ditambahkan!');
    }

    public function update(Request $request, string $id)
    {
        $product = ProductModel::findOrFail($id);

        if (!$product) {
            throw ValidationException::withMessages([
                'error' => 'Data produk tidak ditemukan atau sudah dihapus.'
            ]);
        }

        // policy update data
        $this->authorize('update', $product, 'unauthorized_action');

        $user = auth()->user();
        $oldUpdatedBy  = $product->updated_by;
        $validated = $this->validateProduct($request->all());
        $this->services->update($product, $validated);
        $this->clearProductCaches(
            $product,
            $user->id,
            $oldUpdatedBy
        );

        return redirect()->back()->with('message', 'Perubahan data dari produk ' . $product->name . ' berhasil diperbarui!');
    }

    public function destroy(string $id)
    {
        $product = ProductModel::findOrFail($id);

        if (!$product) {
            throw ValidationException::withMessages([
                'error' => 'Data produk tidak ditemukan atau sudah dihapus.'
            ]);
        }

        // policy penghapusan data
        $this->authorize('delete', $product);

        $user = auth()->user();
        $this->services->delete($product);

        if (!$product->trashed()) {
            throw ValidationException::withMessages([
                'error' => "Gagal menghapus data {$product->name} secara aman."
            ]);
        }
        $this->clearProductCaches(
            $product,
            $user->id,
        );
        return redirect()->back()->with('message', 'Data produk ' . $product->name . ' berhasil dihapus');
    }
    protected function clearProductCaches(
        ProductModel $product,
        ?string $actorUserId = null,
        ?string $oldUpdatedBy = null
    ) {
        $user = auth()->user();
        $this->invalidateCaches([
            ProductRepository::class,
        ], [
            $actorUserId ? ScopeKey::user($actorUserId) : null,
            $oldUpdatedBy ? ScopeKey::user($oldUpdatedBy) : null,
            !empty($product->created_by) ? ScopeKey::user($product->created_by) : null,
            !empty($product->updated_by) ? ScopeKey::user($product->updated_by) : null,
            !empty($user?->id_branch) ? ScopeKey::branch($user->id_branch) : null,
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);
    }
}
