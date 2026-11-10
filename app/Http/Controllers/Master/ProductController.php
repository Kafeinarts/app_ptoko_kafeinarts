<?php

namespace App\Http\Controllers\Master;

use App\Exports\ProductsExport;
use App\Exports\TemplateProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreProductRequest;
use App\Http\Requests\Master\UpdateProductRequest;
use App\Imports\ProductsImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\TransactionItem;
use App\Models\Unit;
use App\Support\AuditLogger;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $outletId = OutletContext::id();

        $products = Product::query()
            ->with([
                'category', 'brand', 'unit',
                'variants.prices' => fn ($q) => $q->where('outlet_id', $outletId),
                'variants.stocks' => fn ($q) => $q->where('outlet_id', $outletId),
            ])
            ->when($request->get('q'), fn ($q, $v) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%$v%")
                ->orWhere('sku', 'like', "%$v%")
                ->orWhere('barcode', 'like', "%$v%")))
            ->when($request->get('category'), fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->get('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->get('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('master.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('master.products.form', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $outletId = OutletContext::id();

        try {
            DB::transaction(function () use ($data, $request, $outletId) {
                $imagePath = $request->hasFile('image')
                    ? $request->file('image')->store('products', 'public')
                    : null;

                $product = Product::query()->create([
                    'category_id' => $data['category_id'],
                    'brand_id' => $data['brand_id'] ?? null,
                    'unit_id' => $data['unit_id'],
                    'sku' => $data['sku'] ?: Product::generateSku($data['name']),
                    'barcode' => $data['barcode'] ?? null,
                    'name' => $data['name'],
                    'image_path' => $imagePath,
                    'description' => $data['description'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                ]);

                $variants = collect($data['variants'] ?? [])->filter(fn ($v) => ! empty($v['name']));
                if ($variants->isEmpty()) {
                    $variants = collect([['name' => 'Default', 'cost_price' => $data['cost_price'], 'selling_price' => $data['selling_price']]]);
                }

                foreach ($variants as $index => $variant) {
                    $created = ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'variant_name' => $variant['name'],
                        'sku_variant' => $product->sku.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    ]);

                    ProductPrice::query()->create([
                        'product_variant_id' => $created->id,
                        'outlet_id' => $outletId,
                        'cost_price' => (float) ($variant['cost_price'] ?? $data['cost_price']),
                        'selling_price' => (float) ($variant['selling_price'] ?? $data['selling_price']),
                    ]);

                    Stock::query()->create([
                        'product_variant_id' => $created->id,
                        'outlet_id' => $outletId,
                        'quantity' => (int) ($data['stock'] ?? 0),
                        'min_stock' => (int) ($data['min_stock'] ?? 5),
                    ]);
                }

                AuditLogger::log('product.created', Product::class, $product->id, null, $product->toArray());
            });

            return redirect()->route('master.products.index')->with('swal_success', 'Produk berhasil ditambahkan.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $product->load(['variants.prices', 'variants.stocks']);

        return view('master.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $outletId = OutletContext::id();

        try {
            DB::transaction(function () use ($data, $request, $product, $outletId) {
                $imagePath = $product->image_path;
                if ($request->hasFile('image')) {
                    if ($imagePath) {
                        Storage::disk('public')->delete($imagePath);
                    }
                    $imagePath = $request->file('image')->store('products', 'public');
                }

                $product->update([
                    'category_id' => $data['category_id'],
                    'brand_id' => $data['brand_id'] ?? null,
                    'unit_id' => $data['unit_id'],
                    'sku' => $data['sku'],
                    'barcode' => $data['barcode'] ?? null,
                    'name' => $data['name'],
                    'image_path' => $imagePath,
                    'description' => $data['description'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                ]);

                $variant = $product->primaryVariant();

                if (! $variant) {
                    $variant = ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'variant_name' => 'Default',
                        'sku_variant' => $product->sku.'-01',
                    ]);
                }

                ProductPrice::query()->updateOrCreate(
                    ['product_variant_id' => $variant->id, 'outlet_id' => $outletId],
                    ['cost_price' => (float) $data['cost_price'], 'selling_price' => (float) $data['selling_price']],
                );

                Stock::query()->updateOrCreate(
                    ['product_variant_id' => $variant->id, 'outlet_id' => $outletId],
                    ['quantity' => (int) ($data['stock'] ?? 0), 'min_stock' => (int) ($data['min_stock'] ?? 5)],
                );

                AuditLogger::log('product.updated', Product::class, $product->id, null, $product->toArray());
            });

            return redirect()->route('master.products.index')->with('swal_success', 'Produk berhasil diperbarui.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        if (TransactionItem::query()
            ->whereHas('variant', fn ($q) => $q->where('product_id', $product->id))
            ->exists()) {
            return back()->with('swal_error', 'Produk sudah dipakai dalam transaksi sehingga tidak dapat dihapus. Nonaktifkan saja.');
        }

        try {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->delete();

            return back()->with('swal_success', 'Produk berhasil dihapus.');
        } catch (Throwable $e) {
            return back()->with('swal_error', 'Produk tidak dapat dihapus: '.$e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new ProductsExport, 'master-produk-'.now()->format('Ymd-His').'.xlsx');
    }

    public function template()
    {
        return Excel::download(new TemplateProductsExport, 'template-import-produk.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']]);

        try {
            $import = new ProductsImport(OutletContext::id());
            Excel::import($import, $request->file('file'));

            return back()->with('swal_success', "Import selesai. {$import->imported} produk berhasil diproses.");
        } catch (Throwable $e) {
            return back()->with('swal_error', 'Import gagal: '.$e->getMessage());
        }
    }

    public function priceDraft()
    {
        $products = Product::query()->with(['category', 'unit', 'variants.prices'])->orderBy('name')->get();

        return view('master.products.price-draft', compact('products'));
    }
}
