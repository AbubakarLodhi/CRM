<?php

use App\Http\Controllers\Asset\AssetPreviewController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoices/{type}/{id}', [InvoiceController::class, 'show'])
    ->name('invoices.show');

Route::get('/assets/preview/{id}', [AssetPreviewController::class, 'show'])
    ->middleware(['web', 'auth:staff,merchant'])
    ->name('assets.preview');

// POS product search endpoint
Route::get('/pos/products', function (Request $request) {
    $user = auth('staff')->user() ?? auth('merchant')->user();

    if (! $user) {
        return response()->json([]);
    }

    $merchantId = $user instanceof Merchant
        ? $user->id
        : $user->merchant_id;

    $query = Product::query()
        ->withoutTrashed()
        ->where('is_active', true)
        ->where('merchant_id', $merchantId);

    if ($search = $request->get('search')) {
        $term = '%'.mb_strtolower(trim($search)).'%';
        $query->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$term])
            ->orWhereRaw('LOWER(sku) LIKE ?', [$term])
        );
    }

    if ($categoryId = $request->get('category_id')) {
        $query->where('category_id', $categoryId);
    }

    return response()->json(
        $query
            ->with('productImage')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'selling_price'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'selling_price' => $p->selling_price,
                'image' => $p->productImage
                    ? 'https://hdojyhoqzioxnbkuxjno.supabase.co/storage/v1/object/public/product-images/products/'.basename($p->productImage->photo_url)
                    : null,
            ])
    );
})->middleware(['web', 'auth:staff,merchant'])->name('pos.products');

// POS variants endpoint
Route::get('/pos/variants', function (Request $request) {
    $productId = $request->get('product_id');
    if (! $productId) {
        return response()->json([]);
    }

    return response()->json(
        ProductVariant::query()
            ->withoutTrashed()
            ->where('product_id', $productId)
            ->limit(50)
            ->get(['id', 'name', 'sku', 'selling_price'])
    );
})->middleware(['web', 'auth:staff,merchant'])->name('pos.variants');

// POS branches endpoint
Route::get('/pos/branches', function (Request $request) {
    $productId = $request->get('product_id');
    if (! $productId) {
        return response()->json([]);
    }

    $user = auth('staff')->user() ?? auth('merchant')->user();
    if (! $user) {
        return response()->json([]);
    }

    $merchantId = $user instanceof Merchant
        ? $user->id
        : $user->merchant_id;

    $hasBranchAssignments = DB::table('branch_products')
        ->where('product_id', $productId)
        ->exists();

    $query = Branch::query()
        ->withoutTrashed()
        ->where('merchant_id', $merchantId);

    if ($hasBranchAssignments) {
        $query->whereExists(fn ($q) => $q->selectRaw(1)
            ->from('branch_products')
            ->whereColumn('branch_products.branch_id', 'branches.id')
            ->where('branch_products.product_id', $productId)
        );
    }

    return response()->json(
        $query->orderBy('name')->get(['id', 'name'])
    );
})->middleware(['web', 'auth:staff,merchant'])->name('pos.branches');

// POS categories endpoint - only shows categories that have products
// POS categories endpoint
Route::get('/pos/categories', function (Request $request) {
    $user = auth('staff')->user() ?? auth('merchant')->user();
    if (! $user) {
        return response()->json([]);
    }

    $merchantId = $user instanceof Merchant
        ? $user->id
        : $user->merchant_id;

    return response()->json(
        Category::query()
            ->where('merchant_id', $merchantId)
            ->whereNull('parent_id')
            ->whereExists(fn ($q) => $q->selectRaw(1)
                ->from('products')
                ->whereColumn('products.category_id', 'categories.id')
                ->where('products.merchant_id', $merchantId)
                ->where('products.is_active', true)
                ->whereNull('products.deleted_at')
            )
            ->orderBy('name')
            ->get(['id', 'name'])
    );
})->middleware(['web', 'auth:staff,merchant'])->name('pos.categories');

Route::get('/assign-images', function () {

    $products = Product::all();

    foreach ($products as $product) {

        $filename = Str::slug($product->name).'.png';

        $path = 'products/'.$filename;

        if (Storage::disk('s3')->exists($path)) {

            $product->image = $path;

            $product->save();
        }
    }

    return 'Images assigned successfully!';
});
