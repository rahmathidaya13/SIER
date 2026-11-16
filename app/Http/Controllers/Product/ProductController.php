<?php

namespace App\Http\Controllers\Product;

use App\Support\ScopeKey;
use App\Traits\UserScope;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Traits\InvalidatesCaches;
use App\Traits\ProductValidation;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Services\Product\ProductService;
use App\Repository\Product\ProductRepository;
use Illuminate\Validation\ValidationException;

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

    public function store(ProductRequest $request)
    {
        $this->authorize('create', ProductModel::class, 'unauthorized_action');

        $validated = $request->validated();
        $user = auth()->user();
        $product = $this->services->store($validated);
        $this->clearProductCaches($product, $user->id);
        return redirect()->back()->with('message', 'Produk baru ' . ucwords($product->name) . ' berhasil ditambahkan!');
    }

    public function update(ProductRequest $request, string $id)
    {
        $product = ProductModel::findOrFail($id);

        if (!$product) {
            throw ValidationException::withMessages([
                'error' => 'Data produk tidak ditemukan atau sudah dihapus.'
            ]);
        }

        // policy update data
        $this->authorize('update', $product, 'unauthorized_action');
        $validated = $request->validated();

        $user = auth()->user();
        $oldUpdatedBy  = $product->updated_by;
        $this->services->update($product, $validated);
        $this->clearProductCaches(
            $product,
            $user->id,
            $oldUpdatedBy
        );

        return redirect()->back()->with('message', 'Perubahan data dari produk ' . ucwords($product->name) . ' berhasil diperbarui!');
    }

    public function destroy(ProductRequest $request, string $id)
    {
        $product = $request->resolveProductModel();
        $user = auth()->user();

        $this->authorize('delete', $product); // policy penghapusan data
        $this->services->delete($product);
        $this->clearProductCaches(
            $product,
            $user->id,
        );
        return redirect()->back()->with('message', 'Data produk ' . ucwords($product->name) . ' berhasil dihapus');
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
