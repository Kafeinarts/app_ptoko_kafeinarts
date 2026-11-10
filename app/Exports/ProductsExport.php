<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::query()->with(['category', 'brand', 'unit', 'variants.prices', 'variants.stocks'])->get();
    }

    public function headings(): array
    {
        return [
            'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Brand', 'Satuan',
            'Harga Beli', 'Harga Jual', 'Stok', 'Min Stok', 'Status',
        ];
    }

    public function map($product): array
    {
        $variant = $product->primaryVariant();
        $price = $variant?->prices->first();
        $stock = $variant?->stocks->sum('quantity') ?? 0;

        return [
            $product->sku,
            $product->barcode,
            $product->name,
            $product->category?->name,
            $product->brand?->name,
            $product->unit?->short_name,
            (float) ($price->cost_price ?? 0),
            (float) ($price->selling_price ?? 0),
            $stock,
            $variant?->stocks->first()?->min_stock ?? 5,
            $product->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
