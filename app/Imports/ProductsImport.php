<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements SkipsErrors, SkipsFailures, SkipsOnError, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    public int $imported = 0;

    public function __construct(private readonly int $outletId) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($row['category'] ?? 'Umum')],
                ['name' => $row['category'] ?? 'Umum', 'is_active' => true],
            );
            $unit = Unit::query()->firstOrCreate(
                ['short_name' => $row['unit'] ?? 'Pcs'],
                ['name' => $row['unit'] ?? 'Pcs'],
            );
            $brand = ! empty($row['brand'])
                ? Brand::query()->firstOrCreate(['name' => $row['brand']])
                : null;

            $sku = $row['sku'] ?: Product::generateSku($row['name']);
            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $category->id,
                    'brand_id' => $brand?->id,
                    'unit_id' => $unit->id,
                    'barcode' => $row['barcode'] ?: null,
                    'name' => $row['name'],
                    'is_active' => true,
                ],
            );

            $variant = ProductVariant::query()->firstOrCreate(
                ['product_id' => $product->id, 'variant_name' => 'Default'],
                ['sku_variant' => $sku.'-01'],
            );

            ProductPrice::query()->updateOrCreate(
                ['product_variant_id' => $variant->id, 'outlet_id' => $this->outletId],
                [
                    'cost_price' => (float) ($row['cost_price'] ?? 0),
                    'selling_price' => (float) ($row['selling_price'] ?? 0),
                ],
            );

            Stock::query()->updateOrCreate(
                ['product_variant_id' => $variant->id, 'outlet_id' => $this->outletId],
                [
                    'quantity' => (int) ($row['stock'] ?? 0),
                    'min_stock' => (int) ($row['min_stock'] ?? 5),
                ],
            );

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
