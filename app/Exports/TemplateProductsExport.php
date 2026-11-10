<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateProductsExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [
            ['KOPI-0001', '8999999999999', 'Es Kopi Susu', 'Minuman', 'Kafeinarts', 'Cup', 8000, 18000, 50, 10],
            ['MAKAN-0001', '', 'Croissant Butter', 'Makanan', 'Kafeinarts', 'Pcs', 12000, 25000, 20, 5],
        ];
    }

    public function headings(): array
    {
        return ['sku', 'barcode', 'name', 'category', 'brand', 'unit', 'cost_price', 'selling_price', 'stock', 'min_stock'];
    }
}
